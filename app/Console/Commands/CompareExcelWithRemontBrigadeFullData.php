<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RemontBrigadeFullData;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CompareExcelWithRemontBrigadeFullData extends Command
{
    protected $signature = 'compare:remont-excel {file}';
    protected $description = 'Сравнить данные Excel-файла с RemontBrigadeFullData';

    public function handle()
    {
        $file = $this->argument('file');
        if (!file_exists($file)) {
            $this->error("Файл не найден: $file");
            return 1;
        }

        // Используем встроенный SimpleXLSX импорт
        $rows = Excel::toArray(new \stdClass(), $file)[0];
        $header = array_shift($rows); // Пропустить заголовок
        $total = count($rows);
        $matched = 0;
        $notFound = 0;
        $diffs = 0;

        foreach ($rows as $i => $row) {
            // Индексы колонок по вашему скрину:
            // 0: Р/с, 1: Бр., 2: НГДУ, 3: Месяц, 4: №, 5: ТК, 6: МК/ККСС, 7: УНВ час, 8: Факт час, 9: Басы, 10: Соны
            $well_number = trim($row[4]);
            $ngdu = trim($row[2]);
            $tk = trim($row[5]);
            $mk_kkss = trim($row[6]);
            $unv_hours = trim($row[7]);
            $actual_hours = trim($row[8]);
            $start_date = $this->parseExcelDate($row[9]);
            $end_date = $this->parseExcelDate($row[10]);

            $query = RemontBrigadeFullData::where('well_number', $well_number)
                ->where('ngdu', $ngdu)
                ->where('tk', $tk)
                ->where('mk_kkss', $mk_kkss)
                ->whereDate('start_date', $start_date)
                ->whereDate('end_date', $end_date);
            $db = $query->first();

            if (!$db) {
                $notFound++;
                $this->warn("[{$i}] Не найдено в БД: скважина $well_number, НГДУ $ngdu, ТК $tk, МК/ККСС $mk_kkss, даты $start_date - $end_date");
                continue;
            }

            $matched++;
            $diff = [];
            if ((float)$db->unv_hours != (float)$unv_hours) {
                $diff[] = "УНВ час: файл=$unv_hours, БД={$db->unv_hours}";
            }
            if ((float)$db->actual_hours != (float)$actual_hours) {
                $diff[] = "Факт час: файл=$actual_hours, БД={$db->actual_hours}";
            }
            if ($diff) {
                $diffs++;
                $this->line("[{$i}] Отличия: скважина $well_number: " . implode('; ', $diff));
            }
        }

        $this->info("Всего строк: $total");
        $this->info("Совпадений: $matched");
        $this->info("Не найдено в БД: $notFound");
        $this->info("Строк с отличиями: $diffs");
        return 0;
    }

    private function parseExcelDate($value)
    {
        // Если дата в формате Excel (число), преобразуем
        if (is_numeric($value)) {
            return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value))->toDateString();
        }
        // Если строка, пробуем парсить
        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Exception $e) {
            return null;
        }
    }
}
