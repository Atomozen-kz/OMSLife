<?php

namespace Database\Seeders;

use App\Models\RemontBrigade;
use App\Models\RemontBrigadesPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class RemontBrigadesPlan2026Seeder extends Seeder
{
    private const YEAR = 2026;

    // ⚠️ Ты сказал: Қаңтар = 2026-02 -> значит OFFSET = 1
    // Если нужно Қаңтар = 2026-01 -> поставь 0
    private const MONTH_OFFSET = 1;

    private const UNV_PLAN_CONST = 390;

    public function run(): void
    {
        $filePath = database_path('seeders/data/Жоспар 2026.xlsx');
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Excel file not found: {$filePath}");
        }

        $spreadsheet = IOFactory::load($filePath);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $sheetTitle = (string)$sheet->getTitle();

            $highestRow = (int)$sheet->getHighestRow();
            $highestColIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());

            // В твоем файле:
            // Row 1: "2026 Жоспар"
            // Row 2: заголовки (№ Бригады, Шебер, Қаңтар, Ақпан, ...)
            $headerRow = 2;
            $dataFromRow = 3;

            // Собираем колонки с месяцами: colIndex => monthNumber(1..12)
            $monthCols = $this->detectMonthColumns($sheet, $headerRow, $highestColIndex);

            if (empty($monthCols)) {
                Log::warning("No month columns detected on sheet: {$sheetTitle}");
                continue;
            }

            for ($row = $dataFromRow; $row <= $highestRow; $row++) {
                // Колонка A = 1: "№ Бригады"
                $brigadeNameRaw = $sheet->getCellByColumnAndRow(1, $row)->getValue();
                $brigadeName = $this->normText($brigadeNameRaw);

                // Берем только ОМС-*
                if ($brigadeName === '' || !preg_match('/^омс-\d+$/u', $brigadeName)) {
                    continue;
                }

                // Вернем нормальный регистр "ОМС-1"
                $brigadeName = mb_strtoupper($brigadeName);

                $brigade = RemontBrigade::query()->where('name', $brigadeName)->first();
                if (!$brigade) {
                    $skipped++;
                    Log::warning("Brigade not found: {$brigadeName} (sheet={$sheetTitle}, row={$row})");
                    continue;
                }

                foreach ($monthCols as $colIndex => $monthNumber) {
                    $planRaw = $sheet->getCellByColumnAndRow($colIndex, $row)->getCalculatedValue();
                    $plan = $this->toInt($planRaw);

                    // если пусто — пропускаем (если хочешь писать 0 — скажи)
                    if ($plan === null) {
                        continue;
                    }

                    // ⚠️ сдвиг месяца по твоему правилу (Қаңтар = 2026-02)
                    [$y, $m] = $this->applyOffset(self::YEAR, $monthNumber, self::MONTH_OFFSET);
                    $monthStr = sprintf('%04d-%02d', $y, $m);

                    $unique = [
                        'brigade_id' => $brigade->id,
                        'month' => $monthStr,
                    ];

                    $data = [
                        'plan' => $plan,
                        'unv_plan' => self::UNV_PLAN_CONST,
                    ];

                    $model = RemontBrigadesPlan::query()->updateOrCreate($unique, $data);

                    if ($model->wasRecentlyCreated) $created++; else $updated++;
                }
            }
        }

        $this->command?->info("✅ RemontBrigadesPlan seeded. created={$created}, updated={$updated}, skipped={$skipped}");
    }

    /**
     * Находит, какие колонки являются месяцами.
     * Игнорирует І-тоқсан/ІІ-тоқсан/ІІІ-тоқсан/ІV-тоқсан и "Жоспар 2026г"
     */
    private function detectMonthColumns($sheet, int $headerRow, int $highestColIndex): array
    {
        $months = [
            'қаңтар' => 1,
            'ақпан' => 2,
            'наурыз' => 3,
            'сәуір' => 4,
            'мамыр' => 5,
            'маусым' => 6,
            'шілде' => 7,
            'тамыз' => 8,
            'қыркүйек' => 9,
            'қазан' => 10,
            'қараша' => 11,
            'желтоқсан' => 12,
        ];

        $result = [];

        for ($col = 1; $col <= $highestColIndex; $col++) {
            $val = $sheet->getCellByColumnAndRow($col, $headerRow)->getValue();
            $key = $this->normText($val);

            if (isset($months[$key])) {
                $result[$col] = $months[$key];
            }
        }

        return $result;
    }

    private function applyOffset(int $year, int $month, int $offset): array
    {
        $m = $month + $offset;
        $y = $year;

        while ($m > 12) {
            $m -= 12;
            $y += 1;
        }
        while ($m < 1) {
            $m += 12;
            $y -= 1;
        }

        return [$y, $m];
    }

    private function toInt(mixed $v): ?int
    {
        if ($v === null) return null;

        if (is_numeric($v)) {
            return (int)round((float)$v);
        }

        $s = trim((string)$v);
        if ($s === '') return null;

        $s = str_replace(',', '.', $s);
        return is_numeric($s) ? (int)round((float)$s) : null;
    }

    private function normText(mixed $v): string
    {
        $s = is_string($v) ? $v : (string)($v ?? '');
        $s = trim(mb_strtolower($s));
        $s = preg_replace('/\s+/u', ' ', $s);
        return $s;
    }
}
