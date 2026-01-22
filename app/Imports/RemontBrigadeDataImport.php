<?php

namespace App\Imports;

use App\Models\RemontBrigade;
use App\Models\RemontBrigadeFullData;
use App\Models\RemontBrigadesPlan;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Carbon\Carbon;

class RemontBrigadeDataImport implements ToModel, WithStartRow, WithBatchInserts, WithCalculatedFormulas
{
    protected string $month;
    protected array $brigadeCache = [];
    protected array $planCache = [];
    protected array $errors = [];
    protected int $rowNumber = 3; // Начинаем с 3, т.к. startRow = 4
    protected ?string $lastNgdu = null; // Последнее значение НГДУ для объединённых ячеек

    public function __construct(string $month)
    {
        $this->month = $month;

        // Предзагружаем все бригады в кэш
        $brigades = RemontBrigade::all();
        foreach ($brigades as $brigade) {
            $this->brigadeCache[$brigade->name] = $brigade->toArray();
        }

        // Предзагружаем все планы на текущий месяц
        $plans = RemontBrigadesPlan::where('month', $this->month)->get();
        foreach ($plans as $plan) {
            $this->planCache[$plan->brigade_id] = $plan;
        }
    }

    public function startRow(): int
    {
        return 4;
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function model(array $row)
    {
        $this->rowNumber++;

        // Пропускаем пустые строки
        if (empty(array_filter($row))) {
            return null;
        }

        // Пропускаем строки с итогами (Барлығы, Жоспар и т.д.)
        $firstCell = trim($row[0] ?? '');
        if (str_contains($firstCell, 'Барлығы') ||
            str_contains($firstCell, 'Жоспар') ||
            str_contains($firstCell, 'ӨМГ')) {
            return null;
        }

        // Номер скважины (колонка C, индекс 2)
        $wellNumber = trim($row[2] ?? '');

        // Пропускаем строки где номер скважины НЕ число (объединённые ячейки типа "Батыс Теңге")
        if (empty($wellNumber) || !is_numeric($wellNumber)) {
            return null;
        }

        // Номер бригады (колонка B, индекс 1)
        $brigadeNumber = trim($row[1] ?? '');
        if (empty($brigadeNumber) || !is_numeric($brigadeNumber)) {
            return null;
        }

        // Поиск бригады в кэше
        $brigade = $this->findBrigade($brigadeNumber);

        if (!$brigade) {
            $this->errors[] = [
                'row' => $this->rowNumber,
                'type' => 'brigade_not_found',
                'message' => "Бригада '{$brigadeNumber}' не найдена в системе",
                'data' => $row,
            ];
            \Log::warning("Import: Бригада '{$brigadeNumber}' не найдена (строка {$this->rowNumber}, скважина {$wellNumber})");
            return null;
        }

        // Проверяем, есть ли план для этой бригады на текущий месяц
        if (!isset($this->planCache[$brigade['id']])) {
            $this->errors[] = [
                'row' => $this->rowNumber,
                'type' => 'plan_not_found',
                'message' => "План для бригады '{$brigadeNumber}' на месяц {$this->month} не найден",
                'data' => $row,
            ];
            return null;
        }

        $plan = $this->planCache[$brigade['id']];

        // Парсим даты (колонки H и I, индексы 7 и 8)
        $startDate = $this->parseDate($row[7] ?? null);
        $endDate = $this->parseDate($row[8] ?? null);

        // Получаем данные из правильных колонок:
        // Колонка D (индекс 3) - ТК
        $tk = $row[3] ?? null;
        // Колонка E (индекс 4) - МК/ККСС
        $mkKkss = $row[4] ?? null;
        // Колонка F (индекс 5) - УНВ сағ (WithCalculatedFormulas вычисляет формулы)
        $unvHours = $this->toFloat($row[5] ?? 0);
        // Колонка G (индекс 6) - Нақты сағ
        $actualHours = $this->toFloat($row[6] ?? 0);
        // Колонка J (индекс 9) - Описание
        $description = $row[9] ?? null;
        // Колонка K (индекс 10) - Бағасы (цена) - пропускаем
        // Колонка L (индекс 11) - НГДУ (объединённые ячейки, но значение должно быть)
        $ngduRaw = $row[11] ?? null;

        // Обработка объединённых ячеек НГДУ - если значение есть, сохраняем его
        if (!empty($ngduRaw)) {
            $this->lastNgdu = $ngduRaw;
        }
        $ngdu = $this->lastNgdu;

        // Отладка - проверяем значения
        if ($this->rowNumber <= 10) {
            \Log::info("Import row {$this->rowNumber}: well={$wellNumber}, unv_raw={$row[5]}, unv={$unvHours}, actual_raw={$row[6]}, actual={$actualHours}, ngdu={$ngdu}");
        }

        // Проверяем, существует ли запись
        $existing = RemontBrigadeFullData::where('plan_id', $plan->id)
            ->where('well_number', $wellNumber)
            ->where('start_date', $startDate)
            ->first();

        if ($existing) {
            // Обновляем существующую запись
            $existing->update([
                'tk' => $tk,
                'mk_kkss' => $mkKkss,
                'unv_hours' => $unvHours,
                'actual_hours' => $actualHours,
                'end_date' => $endDate,
                'description' => $description,
                'ngdu' => $ngdu,
            ]);
            return null;
        }

        // Создаём новую запись
        return new RemontBrigadeFullData([
            'plan_id' => $plan->id,
            'well_number' => $wellNumber,
            'tk' => $tk,
            'mk_kkss' => $mkKkss,
            'unv_hours' => $unvHours,
            'actual_hours' => $actualHours,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'description' => $description,
            'ngdu' => $ngdu,
        ]);
    }

    /**
     * Получить ошибки импорта
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Поиск бригады в кэше
     */
    private function findBrigade(string $brigadeNumber): ?array
    {
        // Прямой поиск
        if (isset($this->brigadeCache[$brigadeNumber])) {
            return $this->brigadeCache[$brigadeNumber];
        }

        // Поиск по формату "ОМС-{номер}"
        $omsName = "ОМС-{$brigadeNumber}";
        if (isset($this->brigadeCache[$omsName])) {
            return $this->brigadeCache[$omsName];
        }

        // Поиск по шаблонам
        foreach ($this->brigadeCache as $name => $brigade) {
            if (str_ends_with($name, "№{$brigadeNumber}") ||
                str_ends_with($name, "-{$brigadeNumber}") ||
                str_ends_with($name, " {$brigadeNumber}") ||
                $name === "ОМС-{$brigadeNumber}") {
                return $brigade;
            }
        }

        return null;
    }

    private function parseDate($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
            }
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function toFloat($value): float
    {
        if (empty($value)) {
            return 0.0;
        }
        // Если значение - строка с формулой (не должно быть с WithCalculatedFormulas, но на всякий случай)
        if (is_string($value) && str_starts_with($value, '=')) {
            return 0.0;
        }
        return (float) str_replace(',', '.', (string) $value);
    }
}
