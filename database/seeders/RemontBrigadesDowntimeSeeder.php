<?php

namespace Database\Seeders;

use App\Models\RemontBrigade;
use App\Models\RemontBrigadesPlan;
use App\Models\RemontBrigadesDowntime;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class RemontBrigadesDowntimeSeeder extends Seeder
{
    // Поменяй год при необходимости
    private const YEAR = 2025;

    public function run(): void
    {
        $filePath = database_path('seeders/data/Простои.xlsx');

        if (!file_exists($filePath)) {
            throw new \RuntimeException("Excel file not found: {$filePath}");
        }

        // Заголовок колонки -> reason key (как в модели RemontBrigadesDowntime)
        $reasonMap = [
            'ремонт па' => RemontBrigadesDowntime::REASON_REMONT_PA,
            'ожидание вахты' => RemontBrigadesDowntime::REASON_WAIT_VAHTA,
            'метеоусловия' => RemontBrigadesDowntime::REASON_WEATHER,
            "ожидание \nца, ацн" => RemontBrigadesDowntime::REASON_WAIT_CA_ACN,
            'ожидание ца, ацн' => RemontBrigadesDowntime::REASON_WAIT_CA_ACN,
            'прочие' => RemontBrigadesDowntime::REASON_OTHER,
        ];

        $spreadsheet = IOFactory::load($filePath);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $sheetName = trim((string)$sheet->getTitle()); // "1", "2", ...
            if (!ctype_digit($sheetName)) {
                Log::warning("Skip sheet: not numeric month title: {$sheetName}");
                continue;
            }

            $monthNum = (int)$sheetName;
            if ($monthNum < 1 || $monthNum > 12) {
                Log::warning("Skip sheet: invalid month number: {$sheetName}");
                continue;
            }

            $month = sprintf('%d-%02d', self::YEAR, $monthNum);

            // В файле: 1-я строка — "Январь 2025", 2-я строка — заголовки таблицы
            // Заголовки находятся на строке 2:
            $headerRow = 2;

            // Колонки:
            // A: №
            // B: № БР
            // C: Мастер
            // D..H: причины
            // I: Всего
            $highestColumn = $sheet->getHighestColumn(); // например "I"
            $highestRow = (int)$sheet->getHighestRow();

            // Считаем заголовки и поймём где какие причины
            $colIndexToReason = []; // например: 4 => 'remont_pa'
            for ($col = 1; $col <= \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn); $col++) {
                $val = $sheet->getCellByColumnAndRow($col, $headerRow)->getValue();
                $key = $this->normHeader($val);

                if (isset($reasonMap[$key])) {
                    $colIndexToReason[$col] = $reasonMap[$key];
                }
            }

            if (empty($colIndexToReason)) {
                Log::warning("No reason columns detected on sheet={$sheetName}");
                continue;
            }

            // Данные начинаются с 3-й строки
            for ($row = 3; $row <= $highestRow; $row++) {
                $brigadeNameRaw = $sheet->getCellByColumnAndRow(2, $row)->getValue(); // колонка B
                $brigadeName = $this->normBrigade($brigadeNameRaw);

                // пропускаем итоги/цеха/пустые
                if ($brigadeName === null || !preg_match('/^ОМС-\d+$/u', $brigadeName)) {
                    continue;
                }

                $brigade = RemontBrigade::query()->where('name', $brigadeName)->first();
                if (!$brigade) {
                    $skipped++;
                    Log::warning("Brigade not found: {$brigadeName} (sheet={$sheetName}, row={$row})");
                    continue;
                }

                $plan = RemontBrigadesPlan::query()
                    ->where('brigade_id', $brigade->id)
                    ->where('month', $month)
                    ->first();

                if (!$plan) {
                    $skipped++;
                    Log::warning("Plan not found: brigade_id={$brigade->id}, month={$month} (sheet={$sheetName}, row={$row})");
                    continue;
                }

                foreach ($colIndexToReason as $colIndex => $reasonKey) {
                    $hoursRaw = $sheet->getCellByColumnAndRow($colIndex, $row)->getCalculatedValue();
                    $hours = $this->toNumber($hoursRaw);

                    // ✅ не пишем пустое/0
                    if ($hours === null || $hours <= 0) {
                        continue;
                    }

                    $unique = [
                        'plan_id' => $plan->id,
                        'brigade_id' => $brigade->id,
                        'reason' => $reasonKey,
                    ];

                    $model = RemontBrigadesDowntime::query()->updateOrCreate($unique, [
                        'hours' => $hours,
                    ]);

                    if ($model->wasRecentlyCreated) $created++; else $updated++;
                }
            }
        }

        $this->command?->info("✅ Downtime seeded. created={$created}, updated={$updated}, skipped={$skipped}");
    }

    private function normHeader(mixed $v): string
    {
        $s = is_string($v) ? $v : (string)($v ?? '');
        $s = trim(mb_strtolower($s));
        // нормализуем пробелы
        $s = preg_replace('/[ \t]+/u', ' ', $s);
        return $s;
    }

    private function normBrigade(mixed $v): ?string
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        if ($s === '') return null;
        // иногда могут быть лишние пробелы: "ОМС - 1"
        $s = preg_replace('/\s+/u', '', $s);   // "ОМС-1" или "ОМС1"
        $s = str_replace('ОМС', 'ОМС-', $s);   // "ОМС1" -> "ОМС-1"
        $s = preg_replace('/-+/u', '-', $s);   // убрать двойные дефисы
        return $s;
    }

    private function toNumber(mixed $v): ?float
    {
        if ($v === null) return null;

        if (is_numeric($v)) return (float)$v;

        $s = trim((string)$v);
        if ($s === '') return null;

        $s = str_replace(',', '.', $s);
        return is_numeric($s) ? (float)$s : null;
    }
}
