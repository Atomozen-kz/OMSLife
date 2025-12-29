<?php

namespace Database\Seeders;

use App\Models\TrainingRecord;
use App\Models\TrainingType;
use App\Models\Sotrudniki;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class TrainingRecordSeeder extends Seeder
{
    // Твои ID типов
    private const TYPE_BIOT    = 6;
    private const TYPE_PROMBEZ = 5;
    private const TYPE_PTM     = 7;

    // Колонки в Excel (1-based): B=2, F=6, G=7, H=8
    private const COL_TABEL   = 2;
    private const COL_BIOT    = 6;
    private const COL_PROMBEZ = 7;
    private const COL_PTM     = 8;

    // В твоём файле заголовок на строке 3, данные с 4
    private const HEADER_ROW = 3;
    private const DATA_FROM_ROW = 4;

    public function run(): void
    {
        $filePath = database_path('seeders/data/Список ТОО ОМС по обязательным обучениям (wecompress.com).xlsx');

        if (!file_exists($filePath)) {
            throw new \RuntimeException("Excel file not found: {$filePath}");
        }

        // Берём validity_period (месяцы) из TrainingType
        $types = TrainingType::query()
            ->whereIn('id', [self::TYPE_BIOT, self::TYPE_PROMBEZ, self::TYPE_PTM])
            ->get()
            ->keyBy('id');

        foreach ([self::TYPE_BIOT, self::TYPE_PROMBEZ, self::TYPE_PTM] as $id) {
            if (!$types->has($id)) {
                throw new \RuntimeException("TrainingType not found in DB: id={$id}");
            }
        }

        $validityMonths = [
            self::TYPE_BIOT    => (int)($types[self::TYPE_BIOT]->validity_period ?? 0),
            self::TYPE_PROMBEZ => (int)($types[self::TYPE_PROMBEZ]->validity_period ?? 0),
            self::TYPE_PTM     => (int)($types[self::TYPE_PTM]->validity_period ?? 0),
        ];

        $spreadsheet = IOFactory::load($filePath);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $sheetTitle = (string)$sheet->getTitle();
            $highestRow = (int)$sheet->getHighestRow();

            // Мини-проверка: на строке заголовка должны быть БиОТ/Промбез/ПТМ
            $hBiot = $this->normText($sheet->getCellByColumnAndRow(self::COL_BIOT, self::HEADER_ROW)->getValue());
            $hProm = $this->normText($sheet->getCellByColumnAndRow(self::COL_PROMBEZ, self::HEADER_ROW)->getValue());
            $hPtm  = $this->normText($sheet->getCellByColumnAndRow(self::COL_PTM, self::HEADER_ROW)->getValue());

            if (!str_contains($hBiot, 'биот') || !str_contains($hProm, 'промбез') || !str_contains($hPtm, 'птм')) {
                Log::warning("Skip sheet (unexpected structure): {$sheetTitle}");
                continue;
            }

            for ($row = self::DATA_FROM_ROW; $row <= $highestRow; $row++) {
                $tabelRaw = $sheet->getCellByColumnAndRow(self::COL_TABEL, $row)->getValue();
                $tabel = $this->normalizeTabel($tabelRaw);

                if ($tabel === null) {
                    continue; // пустая строка
                }

                $sotrudnik = Sotrudniki::query()
                    ->where('tabel_nomer', $tabel)
                    ->first();

                if (!$sotrudnik) {
                    $skipped++;
                    Log::warning("Sotrudniki not found: tabel_nomer={$tabel} (sheet={$sheetTitle}, row={$row})");
                    continue;
                }

                // Даты из F/G/H
                $biotRaw    = $sheet->getCellByColumnAndRow(self::COL_BIOT, $row)->getValue();
                $prombezRaw = $sheet->getCellByColumnAndRow(self::COL_PROMBEZ, $row)->getValue();
                $ptmRaw     = $sheet->getCellByColumnAndRow(self::COL_PTM, $row)->getValue();

                $this->upsertRecord(self::TYPE_BIOT, $sotrudnik->id, $biotRaw, $validityMonths[self::TYPE_BIOT], $created, $updated);
                $this->upsertRecord(self::TYPE_PROMBEZ, $sotrudnik->id, $prombezRaw, $validityMonths[self::TYPE_PROMBEZ], $created, $updated);
                $this->upsertRecord(self::TYPE_PTM, $sotrudnik->id, $ptmRaw, $validityMonths[self::TYPE_PTM], $created, $updated);
            }
        }

        $this->command?->info("✅ TrainingRecord seeded. created={$created}, updated={$updated}, skipped={$skipped}");
    }

    private function upsertRecord(
        int $trainingTypeId,
        int $sotrudnikId,
        mixed $dateRaw,
        int $validityMonths,
        int &$created,
        int &$updated
    ): void {
        $completion = $this->parseDate($dateRaw);

        // если даты нет — запись не создаём
        if (!$completion) {
            return;
        }

        $validity = (clone $completion)->addMonthsNoOverflow(max($validityMonths, 0));

        $unique = [
            'id_training_type' => $trainingTypeId,
            'id_sotrudnik' => $sotrudnikId,
        ];

        $data = [
            'completion_date' => $completion->format('Y-m-d'),
            'validity_date' => $validity->format('Y-m-d'),
        ];

        $model = TrainingRecord::query()->updateOrCreate($unique, $data);

        if ($model->wasRecentlyCreated) $created++; else $updated++;
    }

    /**
     * Парсим дату из Excel:
     * - Excel serial number
     * - DateTime
     * - строка вида "29.01.2025-Б", "10.02.2025-ПР", "21.01.2025-ПТМ"
     * - а также любые варианты с текстом после даты
     */
    private function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') return null;

        // Excel numeric date
        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float)$value))->startOfDay();
            } catch (\Throwable) {
                return null;
            }
        }

        // DateTime
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance(\DateTime::createFromInterface($value))->startOfDay();
        }

        // String: берём первую дату в тексте и игнорим суффиксы
        $s = trim((string)$value);
        if ($s === '') return null;

        // ищем первую дату в формате dd.mm.yyyy (или dd-mm-yyyy / dd/mm/yyyy)
        if (preg_match('/(\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4})/u', $s, $m)) {
            $s = $m[1];
        } else {
            return null;
        }

        $s = str_replace(['/', '-'], '.', $s);

        // пробуем dd.mm.YYYY
        try {
            return Carbon::createFromFormat('d.m.Y', $s)->startOfDay();
        } catch (\Throwable) {
            // иногда год может быть двузначный
            try {
                return Carbon::createFromFormat('d.m.y', $s)->startOfDay();
            } catch (\Throwable) {
                return null;
            }
        }
    }

    private function normalizeTabel(mixed $v): ?string
    {
        if ($v === null) return null;

        // если число (6240 или 6240.0) -> "6240"
        if (is_numeric($v)) {
            $f = (float)$v;
            return ((int)$f == $f) ? (string)((int)$f) : (string)$v;
        }

        $s = trim((string)$v);
        return $s === '' ? null : $s;
    }

    private function normText(mixed $v): string
    {
        $s = is_string($v) ? $v : (string)($v ?? '');
        $s = trim(mb_strtolower($s));
        $s = preg_replace('/\s+/u', ' ', $s);
        return $s;
    }
}
