<?php

namespace App\Orchid\Screens;

use App\Imports\SotrudnikiImport;
use App\Models\OrganizationStructure;
use App\Models\Position;
use App\Models\Sotrudniki;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Orchid\Attachment\File;
use Orchid\Attachment\Models\Attachment;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class OrganizationStructureScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    protected $parentId;
    protected $nameOrg;

    protected $childrenCount;
    protected $sotrudnikCounts;
    public function query(Request $request): iterable
    {
        $this->parentId = $request->get('parent_id', null);

        // Загружаем все подразделения и подсчитываем их
        $allStructures = OrganizationStructure::with('children')->get();

        $this->childrenCount = $allStructures->groupBy('parent_id')
            ->map(fn($children) => $children->pluck('id')->all());

        // Загружаем всех сотрудников
        $this->sotrudnikCounts = Sotrudniki::selectRaw('organization_id, COUNT(*) as count')
            ->groupBy('organization_id')
            ->pluck('count', 'organization_id');

//        if ($this->parentId) {
//            $this->nameOrg = OrganizationStructure::find($this->parentId)->name_ru;
//        }
        return [
            'structures' => OrganizationStructure::where('parent_id', $this->parentId)->paginate(),
            'parent_id' => $this->parentId,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->nameOrg ?? 'Главная';
    }

    public function commandBar(): iterable
    {
        return [
            ModalToggle::make('Импортировать структуру')
                ->modal('importOrganizationCsvModal')
                ->method('importOrganizationCsv'),

            ModalToggle::make('Импортировать сотрудников')
                ->modal('importModal')
                ->method('importExcel')
                ->icon('cloud-upload'),

            Link::make('Должности')
            ->route('platform.positions')
            ->icon('star'),

                ModalToggle::make('Добавить структуру')
                    ->modal('createOrUpdateModal')
                    ->method('createOrUpdate')
                    ->modalTitle('Добавить новую структуру')
                    ->parameters(['structure.parent_id' => $this->parentId])
                    ->icon('plus'),
                ];
    }

    /**
     * The screen's layout elements.
     *
     * @return array
     */
    public function layout(): array
    {
        return [
            Layout::table('structures', [
                TD::make('name_ru', 'Название (RU)')
                    ->render(function (OrganizationStructure $structure) {
                        return Link::make($structure->name_ru)
                            ->route('platform.organization.structure', ['parent_id' => $structure->id]);
                    }),

                TD::make('name_kz', 'Название (KZ)')
                    ->render(function (OrganizationStructure $structure) {
                        return Link::make($structure->name_kz)
                            ->route('platform.organization.structure', ['parent_id' => $structure->id]);
                    }),

                TD::make('subdivision_count', 'Количество<br> подразделений')
                    ->render(function (OrganizationStructure $structure) {
                        return $structure->preloadedChildrenCount($this->childrenCount->toArray());
                    })->alignCenter(),

                TD::make('sotrudniki_count', 'Количество<br> сотрудников')
                    ->render(function (OrganizationStructure $structure) {
                        return $structure->allSotrudnikCount();
                    })->alignCenter(),

//                TD::make('is_promzona', 'Промзона')
//                    ->render(function (OrganizationStructure $structure) {
//                        return $structure->is_promzona ? 'Да' : 'Нет';
//                    }),

                TD::make('Действия')
                    ->render(function (OrganizationStructure $structure) {
                        return ModalToggle::make('Редактировать')
                            ->modal('createOrUpdateModal')
                            ->method('createOrUpdate')
                            ->modalTitle('Редактирование категории')
                            ->asyncParameters(['structure' => $structure->id])
                            ->icon('pencil');
                    }),
            ]),

            Layout::modal('importOrganizationCsvModal', [
                Layout::rows([
                    Input::make('csv_file')
                        ->type('file')
                        ->title('Выберите файл')
                        ->required(),
                ]),
            ])->title('Импорт структуры')->applyButton('Импортировать'),

            Layout::modal('createOrUpdateModal', [
                Layout::rows([
                    Input::make('structure.id')->type('hidden'),

                    Input::make('structure.name_ru')
                        ->title('Название на русском')
                        ->required(),

                    Input::make('structure.name_kz')
                        ->title('Название на казахском')
                        ->required(),

                    Select::make('structure.parent_id')
                        ->title('Родительская категория')
                        ->empty('Не выбрано')
                        ->fromModel(OrganizationStructure::class, 'name_ru', 'id'),

                    Switcher::make('structure.is_promzona')
                        ->sendTrueOrFalse()
                        ->title('Промзона'),
                ]),
            ])
                ->title('Создание или редактирование категории')
                ->applyButton('Сохранить')
                ->async('asyncGetOrganizationStructure')
                ->closeButton('Отмена'),

            Layout::modal('importModal', [
                Layout::rows([
                    Select::make('parent_id')
                        ->title('Выберите структуру')
                        ->fromModel(OrganizationStructure::whereNull('parent_id'), 'name_ru', 'id')
                        ->required(),

                    Input::make('file')
                        ->type('file')
                        ->title('Выберите файл')
                        ->required(),
                ]),
            ])
                ->title('Импорт сотрудников')
                ->method('importExcel')
                ->applyButton('Импортировать')
                ->closeButton('Отмена'),
        ];
    }

    public function importOrganizationCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();

        // Чтение файла CSV с помощью League\Csv
        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(0); // Устанавливаем первую строку как заголовки

        $records = $csv->getRecords(); // Получаем записи как ассоциативные массивы

        foreach ($records as $row) {
            // Проверяем, что все необходимые данные присутствуют
            if (!isset($row['id'], $row['name_ru'], $row['name_kz'])) {
                continue; // Пропускаем строки без необходимых колонок
            }

            // Обновляем или создаём записи на основе id
            OrganizationStructure::updateOrCreate(
                ['id' => trim($row['id'])], // Условие поиска по id
                [
                    'name_ru' => trim($row['name_ru']),
                    'name_kz' => trim($row['name_kz']),
                ] // Обновляемые значения
            );
        }

        Toast::info('Импорт завершен.');
    }

    /**
     * Асинхронно получает данные категории для редактирования.
     *
     * @param OrganizationStructure $structure
     * @return array
     */
    public function asyncGetOrganizationStructure(OrganizationStructure $structure)
    {
        return [
            'structure' => $structure,
        ];
    }

    public function importExcel(Excel $excel, Request $request)
    {
        $request->validate([
            'parent_id' => 'required|exists:organization_structure,id',
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $file = $request->file('file');
        $file_name = 'import_excel/' . time() . '_' . md5($file) . '.xlsx';
        Storage::put($file_name, file_get_contents($file));
        $parentId = $request->input('parent_id');

        Sotrudniki::whereHas('organization', fn($q) => $q->where('parent_id', $parentId))
            ->update(['is_imported' => false]);

        $failedRows = [];

        $excel->import(new class($parentId, $failedRows) implements ToCollection {
            private $parentId;
            private $failedRows;

            public function __construct($parentId, &$failedRows)
            {
                $this->parentId = $parentId;
                $this->failedRows = &$failedRows;
            }

            public function collection(Collection $rows)
            {
                foreach ($rows->skip(1) as $index => $row) {
                    try {
                        $fioRaw = $row[1] ?? null;
                        $tabelNumber = trim(preg_replace('/\s+/', '', $row[2] ?? ''));
                        $structureName = trim(preg_replace('/\s+/', ' ', $row[5] ?? ''));
                        $structureNameKz = trim(preg_replace('/\s+/', ' ', $row[6] ?? $structureName));
                        $positionName = trim(preg_replace('/\s+/', ' ', $row[7] ?? ''));
                        $positionNameKz = trim(preg_replace('/\s+/', ' ', $row[8] ?? $positionName));

                        if (!$fioRaw || !$tabelNumber || !$positionName) {
                            throw new \Exception("Отсутствуют обязательные данные");
                        }

                        $fio = preg_split('/\s+/', trim(ucwords(strtolower($fioRaw))));
                        $lastName = $fio[0] ?? null;
                        $firstName = $fio[1] ?? null;
                        $fatherName = $fio[2] ?? null;
                        if ($structureName){
                            $structure = OrganizationStructure::firstOrCreate(
                                ['name_ru' => $structureName, 'parent_id' => $this->parentId],
                                ['name_kz' => $structureNameKz]
                            );

                            if ($structure->name_kz !== $structureNameKz) {
                                $structure->update(['name_kz' => $structureNameKz]);
                            }
                        }else{
                            $structure->id = $this->parentId;
                        }


                        $position = Position::firstOrCreate(
                            ['name_ru' => $positionName],
                            ['name_kz' => $positionNameKz]
                        );

                        if ($position->name_kz !== $positionNameKz) {
                            $position->update(['name_kz' => $positionNameKz]);
                        }

                        $employee = Sotrudniki::where('tabel_nomer', $tabelNumber)
                            ->whereHas('organization', fn($q) => $q->where('parent_id', $this->parentId))
                            ->first();

                        if (!$employee && $firstName && $lastName) {
                            $employee = Sotrudniki::where('first_name', $firstName)
                                ->where('last_name', $lastName)
                                ->where('father_name', $fatherName)
                                ->first();
                        }

                        if ($employee) {
                            $employee->update([
                                'organization_id' => $structure->id,
                                'position_id' => $position->id,
                                'tabel_nomer' => $tabelNumber,
                                'is_imported' => 1,
                            ]);
                        } else {
                            Sotrudniki::create([
                                'first_name' => $firstName,
                                'last_name' => $lastName,
                                'father_name' => $fatherName,
                                'tabel_nomer' => $tabelNumber,
                                'organization_id' => $structure->id,
                                'position_id' => $position->id,
                                'is_imported' => 1,
                            ]);
                        }

                    } catch (\Exception $e) {
                        $this->failedRows[] = [
                            'row' => $index + 2,
                            'error' => $e->getMessage(),
                        ];
                    }
                }
            }
        }, $file_name);

        if (!empty($failedRows)) {
            $logPath = storage_path('logs/failed_imports_' . now()->format('Y_m_d_H_i_s') . '.txt');
            file_put_contents($logPath, json_encode($failedRows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            Toast::error('Импорт завершён с ошибками. Проверьте лог.');
        } else {
            Toast::success('Импорт сотрудников завершён успешно.');
        }

        Storage::delete($file_name);
    }




//    public function importExcel(Excel $excel, Request $request)
//    {
//        $request->validate([
//            'parent_id' => 'required|exists:organization_structure,id',
//            'file' => 'required|file|mimes:xlsx,xls',
//        ]);
//
//        $file = $request->file('file');
//        $file_name = 'import_excel/'.time().'_'. md5($file). '.xlsx';
//        Storage::put($file_name, file_get_contents($file));
//        $parentId = $request->input('parent_id');
//        $failedRows = [];
////        dd($file_name);
//
//        // Используем Excel для чтения файла
//        $excel->import(new class($parentId, $failedRows) implements ToCollection, WithHeadingRow {
//            private $parentId;
//            private $failedRows;
//
//            public function __construct($parentId, &$failedRows)
//            {
//                $this->parentId = $parentId;
//                $this->failedRows = &$failedRows;
//            }
//
//            public function collection(Collection $rows)
//            {
//                foreach ($rows as $index => $row) {
//                    try {
//                        $structureName = $row['naimenovanie_sp_sp_psp_otdel_tsekh_sluzhba'];
//                        $positionName = $row['dolzhnost_professiya'];
//
//                        $fio = preg_split('/\s+/', trim(ucwords(strtolower($row['fio'])) ?? ''));
//                        //$fio = explode(' ', ucwords(strtolower($row['fio'])));
//
//                        $lastName = $fio[0] ?? null;
//                        $firstName = $fio[1] ?? null;
//                        $fatherName = $fio[2] ?? null;
//                        $tabelNumber = $row['tabelnyy_nomer'];
//
//                        // Найти или создать структуру
//                        $structure = OrganizationStructure::firstOrCreate(
//                            ['name_ru' => $structureName, 'parent_id' => $this->parentId],
//                            ['name_kz' => $structureName]
//                        );
//
//                        // Найти или создать должность
//                        $position = Position::firstOrCreate(
//                            ['name_ru' => $positionName],
//                            ['name_kz' => $positionName]
//                        );
//
//                        // Проверить существование сотрудника
//                        $employee = Sotrudniki::where('first_name', $firstName)
//                            ->where('last_name', $lastName)
//                            ->where('father_name', $fatherName)
//                            ->first();
//
//                        if (!$employee) {
//                            Sotrudniki::create([
//                                'first_name' => $firstName,
//                                'last_name' => $lastName,
//                                'father_name' => $fatherName,
//                                'tabel_nomer' => $tabelNumber,
//                                'organization_id' => $structure->id,
//                                'position_id' => $position->id,
//                            ]);
//                        }else {
//                            // Сотрудник есть. Проверяем, изменилась ли структура или должность
//                            if (
//                                $employee->organization_id !== $structure->id
//                                || $employee->position_id !== $position->id
//                                || $employee->tabel_nomer !== $tabelNumber
//                            ) {
//                                $employee->update([
//                                    'organization_id' => $structure->id,
//                                    'position_id' => $position->id,
//                                    'tabel_nomer' => $tabelNumber,
//                                ]);
//                            }
//                        }
//                    } catch (\Exception $e) {
//                        $this->failedRows[] = [
//                            'row' => $index + 1,
//                            'error' => $e->getMessage(),
//                        ];
//                    }
//                }
//            }
//        }, $file_name);
//
//        // Сохранение логов
//        if (!empty($failedRows)) {
//            $logPath = storage_path('logs/failed_imports_' . now()->format('Y_m_d_H_i_s') . '.txt');
//            file_put_contents($logPath, json_encode($failedRows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
//
//            Toast::error('Импорт завершен с ошибками. Проверьте логи.');
//        }else{
//            Toast::success('Импорт успешен.');
//        }
//        Storage::delete($file_name);
//    }



    /**
     * Создает или обновляет категорию.
     *
     * @param Request $request
     */
    public function createOrUpdate(Request $request)
    {
        $request->validate([
            'structure.name_ru' => 'required|string',
            'structure.name_kz' => 'required|string',
        ]);

        OrganizationStructure::updateOrCreate(
            ['id' => $request->input('structure.id')],
            $request->input('structure')
        );

        Toast::info('Категория успешно сохранена.');
    }
}
