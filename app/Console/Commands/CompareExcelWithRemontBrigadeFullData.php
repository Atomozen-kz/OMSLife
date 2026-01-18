<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RemontBrigadeFullData;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class CompareExcelWithRemontBrigadeFullData extends Command
{
    protected $signature = 'compare:remont-excel {file}';
    protected $description = 'Сравнить данные Excel-файла с RemontBrigadeFullData';

    private $total = 0;
    private $matched = 0;
    private $notFound = 0;
    private $diffs = 0;

    public function handle()
    {
        $file = $this->argument('file');
        if (!file_exists($file)) {
            $this->error("Файл не найден: $file");
            return 1;
        }

        $this->info("Чтение файла: $file");

        // Используем PhpSpreadsheet с опцией чтения вычисленных значений формул
        $inputFileType = IOFactory::identify($file);
        $reader = IOFactory::createReader($inputFileType);
        $reader->setReadDataOnly(false); // Нужно false чтобы формулы вычислялись

        // Читаем файл чанками для экономии памяти
        $chunkFilter = new ChunkReadFilter();

        $chunkSize = 100;
        $startRow = 2; // Пропускаем заголовок
        $rowIndex = 0;
        $hasMoreRows = true;

        while ($hasMoreRows) {
            $chunkFilter->setRows($startRow, $chunkSize);
            $reader->setReadFilter($chunkFilter);

            $spreadsheet = $reader->load($file);
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestRow();

            if ($startRow > $highestRow) {
                $hasMoreRows = false;
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
                break;
            }

            for ($row = $startRow; $row < $startRow + $chunkSize && $row <= $highestRow; $row++) {
                $rowData = [];
                $highestColumn = $worksheet->getHighestColumn();
                $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $cell = $worksheet->getCellByColumnAndRow($col, $row);
                    // Получаем вычисленное значение (для формул)
                    $rowData[] = $cell->getCalculatedValue();
                }

                // Пропускаем пустые строки
                if (empty(array_filter($rowData))) {
                    continue;
                }

                $this->processRow($rowIndex, $rowData);
                $rowIndex++;
            }

            // Освобождаем память после каждого чанка
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            $startRow += $chunkSize;
        }

        $this->info("Всего строк: {$this->total}");
        $this->info("Совпадений: {$this->matched}");
        $this->info("Не найдено в БД: {$this->notFound}");
        $this->info("Строк с отличиями: {$this->diffs}");
        return 0;
    }

    public function processRow(int $i, array $row): void
    {
        // Индексы колонок:
        // 0: Р/с, 1: Бр., 2: НГДУ, 3: Месяц, 4: №, 5: ТК, 6: МК/ККСС, 7: УНВ час, 8: Факт час, 9: Басы, 10: Соны
        $well_number = isset($row[4]) ? trim((string)$row[4]) : '';
        $ngdu = isset($row[2]) ? trim((string)$row[2]) : '';
        $unv_hours = isset($row[7]) ? trim((string)$row[7]) : '';
        $start_date = isset($row[9]) ? $this->parseExcelDate($row[9]) : null;

        if (empty($well_number) || empty($ngdu) || empty($start_date)) {
            return;
        }

        $this->total++;

        $db = RemontBrigadeFullData::where('well_number', $well_number)
            ->whereLike('ngdu' ,'%'.$ngdu.'%')
            ->whereDate('start_date', $start_date)
            ->first();

        if (!$db) {
            $this->notFound++;
            $this->warn("[{$i}] Не найдено в БД: скважина $well_number, НГДУ $ngdu, дата $start_date");
            return;
        }

        $this->matched++;
        if ((float)$db->unv_hours != (float)$unv_hours) {
            $this->diffs++;
            $oldValue = $db->unv_hours;

            // Обновляем данные в БД
            $db->unv_hours = (float)$unv_hours;
            $db->save();

            $this->info("Обновлено [ID={$db->id}]: скважина $well_number, НГДУ $ngdu, дата $start_date — УНВ было = $oldValue, стало = $unv_hours");
        }
    }

    private function parseExcelDate($value)
    {
        if (empty($value)) {
            return null;
        }

        // PhpSpreadsheet может вернуть DateTimeInterface для дат
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->toDateString();
        }

        // Если дата в формате Excel (число), преобразуем через PhpSpreadsheet
        if (is_numeric($value)) {
            return Carbon::instance(Date::excelToDateTimeObject($value))->toDateString();
        }

        // Если строка, пробуем парсить
        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Exception $e) {
            return null;
        }
    }
}
