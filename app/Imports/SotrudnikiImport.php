<?php

namespace App\Imports;

use App\Models\Sotrudniki;
use App\Models\OrganizationStructure;
use App\Models\Position;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Facades\DB;

class SotrudnikiImport implements ToModel, WithHeadingRow, WithBatchInserts, WithChunkReading
{
    private $successCount = 0;
    private $failureCount = 0;

    /**
     * Обработка каждой строки Excel-файла.
     *
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Оборачиваем в транзакцию для обеспечения целостности данных
        return DB::transaction(function () use ($row) {
            try {
                // Чтение данных из строки
                $psp = trim($row['psp']); // Первый столбец: ПСП
                $tabel_nomer = trim($row['tabelnyy_nomer']); // Второй столбец: Табельный номер
                $fio = trim($row['fio']); // Третий столбец: ФИО
                $naimenovanie_sp = trim($row['naimenovanie_sp_sp_psp_otdel_tsekh_sluzhba']); // Четвёртый столбец: Наименование СП/СП ПСП
                $doljnost = trim($row['dolzhnost_professiya']); // Пятый столбец: Должность/профессия

                // Разделение ФИО на фамилию, имя и отчество
                $fioParts = explode(' ', $fio);
                $lastName = $fioParts[0] ?? null;
                $firstName = $fioParts[1] ?? null;
                $fatherName = isset($fioParts[2]) ? implode(' ', array_slice($fioParts, 2)) : null;

                // Собираем полное имя
                $fullName = trim("$lastName $firstName $fatherName");

                // Проверяем и добавляем ПСП в organization_structure
                $pspOrganization = OrganizationStructure::firstOrCreate(
                    ['name_ru' => $psp, 'parent_id' => null],
                    ['name_kz' => $psp]
                );

                // Проверяем и добавляем Наименование СП/СП ПСП
                $naimenovanieOrganization = OrganizationStructure::firstOrCreate(
                    ['name_ru' => $naimenovanie_sp, 'parent_id' => $pspOrganization->id],
                    ['name_kz' => $naimenovanie_sp]
                );

                // Проверяем и добавляем должность в positions
                $position = Position::firstOrCreate(
                    ['name_ru' => $doljnost],
                    ['name_kz' => $doljnost]
                );

                DB::table('sotrudniki')->updateOrInsert(
                    ['tabel_nomer' => $tabel_nomer],
                [
                    'full_name' => $fullName,
                    'organization_id' => $naimenovanieOrganization->id,
                    'position_id' => $position->id,
                ]);

                // Проверяем, существует ли сотрудник с таким табельным номером
//                $sotrudnik = Sotrudniki::create(
////                    [
////                    ],
//                    [   'tabel_nomer' => $tabel_nomer,
//                        'last_name' => $lastName,
//                        'first_name' => $firstName,
//                        'father_name' => $fatherName,
//                        'organization_id' => $naimenovanieOrganization->id,
//                        'position_id' => $position->id,
//                    ]
//                );

                $this->successCount++;
//                return $sotrudnik;
            } catch (\Exception $e) {
                // Логируем ошибку и продолжаем импорт
                Log::error('Ошибка при импорте сотрудника: ' . $e->getMessage());
                $this->failureCount++;
                return null;
            }
        },2);
    }

    /**
     * Получение количества успешно импортированных записей.
     *
     * @return int
     */
    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    /**
     * Получение количества неудачных импортов.
     *
     * @return int
     */
    public function getFailureCount(): int
    {
        return $this->failureCount;
    }

    /**
     * Размер пакета для импорта.
     *
     * @return int
     */
    public function batchSize(): int
    {
        return 100; // Количество записей, обрабатываемых за один раз
    }

    /**
     * Размер чанка для чтения.
     *
     * @return int
     */
    public function chunkSize(): int
    {
        return 100; // Количество записей, читаемых за один раз
    }
}
