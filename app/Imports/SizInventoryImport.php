<?php

namespace App\Imports;

use App\Models\SizType;
use App\Models\SizInventory;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SizInventoryImport implements ToCollection
{
    private $mode;
    private $errors = [];
    private $processedCount = 0;
    private $processedTypes = []; // Для отслеживания обработанных типов СИЗ

    /**
     * Конструктор
     *
     * @param string $mode 'replace' или 'update'
     */
    public function __construct(string $mode = 'replace')
    {
        $this->mode = $mode;
    }

    /**
     * Обработка коллекции данных из Excel
     */
    public function collection(Collection $rows)
    {
        DB::beginTransaction();

        try {
            if ($rows->isEmpty()) {
                $this->errors[] = ['row' => 0, 'error' => 'Файл пустой'];
                Log::warning('Импорт СИЗ: файл пустой');
                return;
            }

            // Ищем строку с заголовками (где во 2-й колонке "ЕИ")
            $headerRowIndex = null;
            $headers = null;

            foreach ($rows as $index => $row) {
                $rowArray = $row->toArray();
                // Проверяем, есть ли во 2-й колонке (индекс 1) "ЕИ" или "Единица"
                if (isset($rowArray[1]) &&
                    (mb_strtolower(trim($rowArray[1])) === 'еи' ||
                     mb_stripos($rowArray[1], 'единица') !== false ||
                     mb_strtolower(trim($rowArray[1])) === 'комплект')) {
                    $headerRowIndex = $index;
                    $headers = $row;
                    break;
                }
            }

            if (!$headers || $headerRowIndex === null) {
                $this->errors[] = ['row' => 0, 'error' => 'Не найдена строка с заголовками. Убедитесь, что во 2-й колонке есть "ЕИ"'];
                Log::warning('Импорт СИЗ: не найдена строка с заголовками');
                return;
            }

            Log::info('Импорт СИЗ: найдена строка заголовков', ['row_index' => $headerRowIndex + 1]);

            // Преобразуем заголовки в массив
            $headerArray = $headers->toArray();

            Log::info('Импорт СИЗ: заголовки', ['headers' => $headerArray]);

            // Получаем размеры из заголовков (начиная с 3-й колонки - индекс 2)
            $sizes = [];
            for ($i = 2; $i < count($headerArray); $i++) {
                if (!empty($headerArray[$i])) {
                    $sizes[$i] = trim($headerArray[$i]);
                }
            }

            Log::info('Импорт СИЗ: найдено размеров', ['count' => count($sizes), 'sizes' => $sizes]);

            // Обрабатываем строки данных (пропускаем строки до заголовков и саму строку заголовков)
            $dataRows = $rows->slice($headerRowIndex + 1);
            $rowNumber = $headerRowIndex + 1; // Начинаем счет со строки заголовков

            Log::info('Импорт СИЗ: начало обработки', [
                'mode' => $this->mode,
                'total_rows' => $dataRows->count(),
                'start_from_row' => $headerRowIndex + 2
            ]);

            foreach ($dataRows as $row) {
                $rowNumber++;

                try {
                    // Преобразуем строку в массив
                    $rowArray = $row->toArray();

                    // Пропускаем пустые строки
                    if (empty(array_filter($rowArray))) {
                        Log::debug("Импорт СИЗ: строка {$rowNumber} пустая, пропускаем");
                        continue;
                    }

                    // Получаем название вида СИЗ (первая колонка - индекс 0)
                    $sizTypeName = isset($rowArray[0]) ? trim($rowArray[0]) : null;

                    if (empty($sizTypeName)) {
                        Log::debug("Импорт СИЗ: строка {$rowNumber} без названия, пропускаем");
                        continue; // Пропускаем строки без названия
                    }

                    Log::debug("Импорт СИЗ: обработка строки {$rowNumber}", [
                        'siz_type_name' => $sizTypeName,
                        'data' => $rowArray
                    ]);

                    // Ищем тип СИЗ по названию (на русском или казахском)
                    $sizType = SizType::where('name_ru', $sizTypeName)
                        ->orWhere('name_kz', $sizTypeName)
                        ->first();

                    if (!$sizType) {
                        $errorMsg = "Вид СИЗ '{$sizTypeName}' не найден в базе данных. Сначала создайте вид СИЗ.";
                        $this->errors[] = [
                            'row' => $rowNumber,
                            'error' => $errorMsg
                        ];
                        Log::warning("Импорт СИЗ: строка {$rowNumber} - {$errorMsg}");
                        continue;
                    }

                    Log::debug("Импорт СИЗ: найден тип СИЗ", [
                        'id' => $sizType->id,
                        'name' => $sizType->name_ru
                    ]);

                    // Если режим "полная замена" и это первое появление этого типа СИЗ в файле
                    if ($this->mode === 'replace' && !in_array($sizType->id, $this->processedTypes)) {
                        $deletedCount = SizInventory::where('siz_type_id', $sizType->id)->count();
                        SizInventory::where('siz_type_id', $sizType->id)->delete();
                        $this->processedTypes[] = $sizType->id;
                        Log::info("Импорт СИЗ: удалено старых записей для типа '{$sizType->name_ru}': {$deletedCount}");
                    }

                    // Обрабатываем каждый размер
                    $createdCount = 0;
                    $updatedCount = 0;

                    foreach ($sizes as $columnIndex => $size) {
                        // Получаем количество из соответствующей ячейки
                        $quantity = isset($rowArray[$columnIndex]) ? $rowArray[$columnIndex] : null;

                        // Преобразуем количество в число (пустые значения = 0)
                        if ($quantity === null || $quantity === '' || $quantity === 0 || $quantity === '0') {
                            $quantity = 0;
                        } else {
                            $quantity = is_numeric($quantity) ? (int)$quantity : 0;
                        }

                        Log::debug("Импорт СИЗ: создание/обновление записи", [
                            'siz_type' => $sizType->name_ru,
                            'size' => $size,
                            'quantity' => $quantity,
                            'mode' => $this->mode
                        ]);

                        // Обновляем или создаем запись
                        if ($this->mode === 'update') {
                            // Режим обновления - суммируем количество
                            $existing = SizInventory::where('siz_type_id', $sizType->id)
                                ->where('size', $size)
                                ->first();

                            if ($existing) {
                                $oldQuantity = $existing->quantity;
                                $existing->quantity += $quantity;
                                $existing->save();
                                $updatedCount++;
                                Log::debug("Импорт СИЗ: обновлено", [
                                    'size' => $size,
                                    'old_quantity' => $oldQuantity,
                                    'new_quantity' => $existing->quantity
                                ]);
                            } else {
                                SizInventory::create([
                                    'siz_type_id' => $sizType->id,
                                    'size' => $size,
                                    'quantity' => $quantity,
                                ]);
                                $createdCount++;
                            }
                        } else {
                            // Режим полной замены - создаем новую запись
                            try {
                                SizInventory::create([
                                    'siz_type_id' => $sizType->id,
                                    'size' => $size,
                                    'quantity' => $quantity,
                                ]);
                                $createdCount++;
                            } catch (\Exception $e) {
                                // Если запись уже существует (дубликат в файле), обновляем количество
                                $existing = SizInventory::where('siz_type_id', $sizType->id)
                                    ->where('size', $size)
                                    ->first();
                                if ($existing) {
                                    $existing->quantity = $quantity;
                                    $existing->save();
                                    $updatedCount++;
                                    Log::warning("Импорт СИЗ: дубликат в файле, обновлено", [
                                        'size' => $size,
                                        'quantity' => $quantity
                                    ]);
                                }
                            }
                        }
                    }

                    if ($createdCount > 0 || $updatedCount > 0) {
                        Log::info("Импорт СИЗ: строка {$rowNumber} обработана", [
                            'siz_type' => $sizType->name_ru,
                            'created' => $createdCount,
                            'updated' => $updatedCount
                        ]);
                    }

                    $this->processedCount++;
                } catch (\Exception $e) {
                    $this->errors[] = [
                        'row' => $rowNumber,
                        'error' => $e->getMessage()
                    ];
                    Log::error("Ошибка импорта СИЗ в строке {$rowNumber}: " . $e->getMessage(), [
                        'exception' => $e->getTraceAsString()
                    ]);
                }
            }

            DB::commit();

            Log::info('Импорт СИЗ завершен', [
                'processed' => $this->processedCount,
                'errors' => count($this->errors)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->errors[] = ['row' => 0, 'error' => 'Критическая ошибка: ' . $e->getMessage()];
            Log::error('Критическая ошибка импорта СИЗ: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Получить список ошибок
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Получить количество обработанных строк
     */
    public function getProcessedCount(): int
    {
        return $this->processedCount;
    }
}
