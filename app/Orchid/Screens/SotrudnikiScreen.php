<?php

namespace App\Orchid\Screens;

use App\Http\Requests\OrchidSotrudnikiRequest;
use App\Models\OrganizationStructure;
use App\Models\Position;
use App\Models\Sotrudniki;
use App\Models\SotrudnikiCodes;
use App\Orchid\Filters\SotrudnikiFioFilter;
use App\Orchid\Layouts\rows\addOrUpdateSotrudnikModal;
use App\Orchid\Layouts\SotrudnikiSelection;
use App\Orchid\Layouts\SpisokSotrudnikovTable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class SotrudnikiScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'sotrudniki' => Sotrudniki::with(['organization', 'position'])
                ->select('*', DB::raw("CONCAT(last_name, ' ', first_name, ' ', father_name) AS fio"))
                ->filters()
                ->filtersApply([SotrudnikiFioFilter::class])
                ->paginate(),
        ];
    }

    public function filters(): array
    {
        return [
            SotrudnikiFioFilter::class,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Управление сотрудниками';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            // Пример: кнопка, открывающая модальное окно
            ModalToggle::make('Импорт кодов для молока')
                ->modal('importModal')
                ->method('importSotrudnikiCsv'), // метод, который будет вызываться при загрузке файла


            ModalToggle::make('Добавить сотрудника')
                ->modal('createOrUpdateSotrudnikaModal')
                ->modalTitle('Добавить сотрудника')
                ->method('createOrUpdateSotrudnika')
                ->icon('plus'),
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            SotrudnikiSelection::class,

            // Таблица список сотрудников
            SpisokSotrudnikovTable::class,

            // Макет модального окна
            Layout::modal('importModal', [
                Layout::rows([
                    // Поле для загрузки файла
                    Input::make('csv_file')
                        ->type('file')
                        ->title('Выберите CSV-файл для импорта')
                        ->acceptedFiles('.csv'),
                    // Если хотите Excel, можно .xlsx, .xls и пр.
                ]),
            ])
                ->title('Импорт из CSV')
                ->applyButton('Импорт'), // Текст на кнопке подтверждения


            // Модальное окно для добавление или редактирвоание сотрудника
            Layout::modal('createOrUpdateSotrudnikaModal', [
                addOrUpdateSotrudnikModal::class
            ])->async('asyncsotrudnik')
            ->applyButton('Сохранить')
            ->closeButton('Отмена')
        ];
    }

    public function asyncsotrudnik(Sotrudniki $sotrudnik): array
    {
        return [
            'sotrudnik' => $sotrudnik,
        ];
    }

    public function createOrUpdateSotrudnika(OrchidSotrudnikiRequest  $request)
    {
        // Поиск сотрудника по ID, если существует
        $sotrudnik = Sotrudniki::find($request->input('sotrudnik.id'));

        if ($sotrudnik) {
            // Обновление данных существующего сотрудника
            $sotrudnik->update($request->sotrudnik);
            Toast::info('Данные сотрудника обновлены!');
        } else {
            // Создание нового сотрудника
            Sotrudniki::create($request->get('sotrudnik'));
            Toast::info('Сотрудник успешно добавлен!');
        }
    }

    // Метод для удаления сотрудника
    public function remove($id)
    {
        Sotrudniki::findOrFail($id)->delete();
        Toast::info('Сотрудник успешно удален');
    }


    public function importSotrudnikiCsv(Request $request)
    {
        // 1. Валидация файла
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        // 2. Получаем путь к загруженному файлу
        $file = $request->file('csv_file');
        $path = $file->getRealPath();

        // Создаем файл логов с требуемым именем
        $logFile = storage_path('logs/qr_milk_import_'.date('Y-m-d_H-i-s').'.log');
        file_put_contents($logFile, "=== Импорт QR для молока: " . now() . " ===\n");

        // 3. Создаём ридер для CSV (League\Csv)
        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(0); // Первая строка - это заголовки

        $records = $csv->getRecords(); // получаем итератор записей

        $notFound = []; // для суммарного вывода/подсчёта
        $rowNumber = 1; // номер обрабатываемой записи (отсчёт произвольный)

        foreach ($records as $row) {
            $rowNumber++;

            // Ожидаемые колонки: company, psp, tabel_number, full_name, qr
            $psp         = trim($row['psp'] ?? $row['PSP'] ?? '');
            $tabelNumber = trim($row['tabel_number'] ?? $row['tabel_nomer'] ?? $row['tabelNumber'] ?? '');
            $qrCode      = trim($row['qr'] ?? $row['QR'] ?? '');
            $fullName    = trim($row['full_name'] ?? $row['fullName'] ?? '');
            $company     = trim($row['company'] ?? '');

            // Пропускаем пустые qr или tabel
            if (empty($qrCode) || empty($tabelNumber) || empty($psp)) {
                $message = "[ROW {$rowNumber}] Пропущено (недостаточно данных): company={$company}; psp={$psp}; tabel={$tabelNumber}; qr={$qrCode}; fio={$fullName}";
                file_put_contents($logFile, $message . PHP_EOL, FILE_APPEND);
                $notFound[] = $message;
                continue;
            }

            // 4. Находим организацию-родителя по name_ru == psp
            $organization = OrganizationStructure::where('name_ru', $psp)->first();

            if (!$organization) {
                $message = "[ROW {$rowNumber}] Не найдено подразделение PSP: company={$company}; psp={$psp}; tabel={$tabelNumber}; fio={$fullName}; qr={$qrCode}";
                file_put_contents($logFile, $message . PHP_EOL, FILE_APPEND);
                $notFound[] = $message;
                continue;
            }

            // 5. Получаем все связанные организации (включая корневую)
            $organizationIds = $organization->allRelatedOrganizationIds();

            // 6. Ищем сотрудника по tabel_number внутри найденных организаций.
            // Поддерживаем возможные варианты названий поля в базе: 'tabel_nomer' и 'tabel_number'
            $employeeQuery = Sotrudniki::whereIn('organization_id', $organizationIds)
                ->where(function ($q) use ($tabelNumber) {
                    $q->where('tabel_nomer', $tabelNumber);
                });

            $employee = $employeeQuery->first();

            if ($employee) {
                try {
                    // Удаляем все существующие qr-коды для сотрудника
                    SotrudnikiCodes::where('sotrudnik_id', $employee->id)->where('type', 'milk')->delete();

                    // Создаём новую запись с qr-кодом
                    SotrudnikiCodes::create([
                        'sotrudnik_id' => $employee->id,
                        'code' => $qrCode,
                        'type' => 'milk'
                    ]);

//                    // Логируем успешное обновление
//                    $successMessage = "[ROW {$rowNumber}] Обновлён QR для сотрудника: sotrudnik_id={$employee->id}; psp={$psp}; tabel={$tabelNumber}; qr={$qrCode}";
//                    file_put_contents($logFile, $successMessage . PHP_EOL, FILE_APPEND);

                } catch (\Exception $e) {
                    // Логируем ошибку при попытке обновить коды
                    $errorMessage = "[ROW {$rowNumber}] Ошибка при обновлении QR для sotrudnik_id={$employee->id}: " . $e->getMessage();
                    file_put_contents($logFile, $errorMessage . PHP_EOL, FILE_APPEND);
                    $notFound[] = $errorMessage;
                }
            } else {
                // Если не нашли по табельному в этих подразделениях — логируем
                $message = "[ROW {$rowNumber}] Не найден сотрудник внутри PSP: psp={$psp}; tabel={$tabelNumber}; fio={$fullName}; qr={$qrCode}";
                file_put_contents($logFile, $message . PHP_EOL, FILE_APPEND);
                $notFound[] = $message;
            }
        }

        // 7. Логируем и показываем предупреждение, если были не найденные строки
        if (!empty($notFound)) {
            file_put_contents($logFile, "\n=== Итоги импорта: не найдены " . count($notFound) . " записей ===\n", FILE_APPEND);
            Toast::warning('Не найдены некоторые записи при импорте. Смотрите лог: ' . $logFile);
        } else {
            file_put_contents($logFile, "\n=== Импорт успешно завершён, все записи обработаны ===\n", FILE_APPEND);
        }

        Toast::info('Импорт завершён!');
        return back();
    }

}
