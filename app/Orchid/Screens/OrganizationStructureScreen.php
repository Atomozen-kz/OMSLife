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

//            ModalToggle::make('Импортировать сотрудников')
//                ->modal('importModal')
//                ->method('importExcel')
//                ->parameters(['parent_id' => $this->parentId])
//                ->icon('cloud-upload'),

//            Link::make('Должности')
//            ->route('platform.positions')
//            ->icon('star'),

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
//                    ->render(function (OrganizationStructure $structure) {
//                        return Link::make($structure->name_ru)
//                            ->route('platform.organization.structure', ['parent_id' => $structure->id]);
//                    })
                ,

                TD::make('name_kz', 'Название (KZ)')
//                    ->render(function (OrganizationStructure $structure) {
//                        return Link::make($structure->name_kz)
//                            ->route('platform.organization.structure', ['parent_id' => $structure->id]);
//                    })
                ,

//                TD::make('subdivision_count', 'Количество<br> подразделений')
//                    ->render(function (OrganizationStructure $structure) {
//                        return $structure->preloadedChildrenCount($this->childrenCount->toArray());
//                    })->alignCenter(),

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

            Layout::modal('createOrUpdateModal', [
                Layout::rows([
                    Input::make('structure.id')->type('hidden'),

                    Input::make('structure.name_ru')
                        ->title('Название на русском')
                        ->required(),

                    Input::make('structure.name_kz')
                        ->title('Название на казахском')
                        ->required(),

//                    Select::make('structure.parent_id')
//                        ->title('Родительская категория')
//                        ->empty('Не выбрано')
//                        ->fromModel(OrganizationStructure::class, 'name_ru', 'id'),

//                    Switcher::make('structure.is_promzona')
//                        ->sendTrueOrFalse()
//                        ->title('Промзона'),
                ]),
            ])
                ->title('Создание или редактирование категории')
                ->applyButton('Сохранить')
                ->async('asyncGetOrganizationStructure')
                ->closeButton('Отмена'),

            Layout::modal('importModal', [
                Layout::rows([
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

            // Обновляем если существует, иначе создаём новую запись с указанным id
            $id = (int)trim($row['id']);
            $org = OrganizationStructure::find($id);
            if ($org) {
                $org->update([
                    'name_ru' => trim($row['name_ru']),
                    'name_kz' => trim($row['name_kz']),
                ]);
            } else {
                $org = new OrganizationStructure([
                    'name_ru' => trim($row['name_ru']),
                    'name_kz' => trim($row['name_kz']),
                ]);
                $org->id = $id; // ручная установка первичного ключа
                $org->save();
            }
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
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $file = $request->file('file');
        $file_name = 'import_excel/' . time() . '_' . md5($file) . '.xlsx';
        Storage::put($file_name, file_get_contents($file));

        $failedRows = [];

        $excel->import(new class($failedRows) implements ToCollection {
            private $failedRows;

            public function __construct(&$failedRows)
            {
                $this->failedRows = &$failedRows;
            }

            public function collection(Collection $collection)
            {
                $rows = $collection;
                if ($rows->isEmpty()) {
                    return;
                }

                // Заголовок (первая строка) для попытки определения индексов
                $header = $rows->first();
                $normalize = function ($v) {
                    return mb_strtolower(trim(preg_replace('/\s+/', ' ', (string)$v)));
                };
                $findIndex = function ($names) use ($header, $normalize) {
                    foreach ($header as $idx => $val) {
                        $n = $normalize($val);
                        foreach ((array)$names as $name) {
                            if ($n === $normalize($name)) {
                                return (int)$idx;
                            }
                        }
                    }
                    return null;
                };

                // Возможные названия столбцов
                $fioIdx      = $findIndex(['фамилия имя отчество','фио','fio']);
                $iinIdx      = $findIndex(['иин','iin']);
                $tabelIdx    = $findIndex(['табельный номер','tabel_nomer','tabel']);
                $structRuIdx = $findIndex(['подразделение','структура','организация','подразделение (рус)','организация (ru)']);
                $structKzIdx = $findIndex(['наименование подразделения на казахском языке','структура (каз)','подразделение (каз)','организация (kz)']);
                $posRuIdx    = $findIndex(['должность','должность (ru)','position (ru)','лауазымы']);
                $posKzIdx    = $findIndex(['наименование должности на казахском','должность (каз)','position (kz)','лауазымы (каз)']);

                // Если не распознали заголовок — применяем индексы по новому формату (A=0, B=1,...)
                $useHeader = !is_null($fioIdx) || !is_null($iinIdx) || !is_null($tabelIdx);
                if (!$useHeader) {
                    $fioIdx      = 1; // B
                    $iinIdx      = 2; // C
                    $tabelIdx    = 3; // D
                    $structKzIdx = 4; // E
                    $structRuIdx = 5; // F
                    $posKzIdx    = 6; // G
                    $posRuIdx    = 7; // H
                }

                $iterable = $useHeader ? $rows->slice(1) : $rows;

                foreach ($iterable as $index => $row) {
                    try {
                        $getVal = function ($r, $idx) {
                            return is_null($idx) ? null : (isset($r[$idx]) ? trim((string)$r[$idx]) : null);
                        };

                        $fioRaw       = $getVal($row, $fioIdx);
                        $iinRaw       = $getVal($row, $iinIdx);
                        $tabelNumber  = preg_replace('/\s+/', '', (string)$getVal($row, $tabelIdx));
                        $structureNameKz = $getVal($row, $structKzIdx);
                        $structureNameRu = $getVal($row, $structRuIdx);
                        $positionNameKz  = $getVal($row, $posKzIdx);
                        $positionNameRu  = $getVal($row, $posRuIdx);

                        // Обязательные поля: ФИО, ИИН, табельный номер, должность (RU)
                        if (!$fioRaw || !$iinRaw || !$tabelNumber || !$positionNameRu) {
                            throw new \Exception('Отсутствуют обязательные данные (ФИО/ИИН/Табельный номер/Должность)');
                        }

                        // Валидация и нормализация ИИН (12 цифр, с учетом ведущих нулей)
                        // Удаляем все нецифровые символы
                        $iin = preg_replace('/\D+/', '', $iinRaw);

                        // Если ИИН короче 12 цифр (из-за потерянных ведущих нулей), дополняем нулями слева
                        if (strlen($iin) > 0 && strlen($iin) < 12) {
                            $iin = str_pad($iin, 12, '0', STR_PAD_LEFT);
                        }

                        // Финальная проверка: должно быть ровно 12 цифр
                        if (strlen($iin) !== 12) {
                            throw new \Exception('ИИН некорректен (должен содержать 12 цифр): ' . $iinRaw . ' -> ' . $iin);
                        }

                        // Нормализуем ФИО
                        $fioParts = preg_split('/\s+/', trim($fioRaw));
                        $lastName   = $fioParts[0] ?? '';
                        $firstName  = $fioParts[1] ?? '';
                        $fatherName = $fioParts[2] ?? '';
                        $fullName = trim("$lastName $firstName $fatherName");

                        // Определяем структуру (ищем по RU, fallback на KZ). Case-insensitive.
                        $structureNameRuNorm = trim($structureNameRu ?: $structureNameKz ?: '');
                        $structureNameKzNorm = trim($structureNameKz ?: $structureNameRuNorm);
                        $structure = null;
                        if ($structureNameRuNorm !== '') {
                            $structure = OrganizationStructure::whereRaw('LOWER(TRIM(name_ru)) = ?', [mb_strtolower($structureNameRuNorm)])
                                ->orWhereRaw('LOWER(TRIM(name_kz)) = ?', [mb_strtolower($structureNameRuNorm)])
                                ->first();
                        }

                        if (!$structure && $structureNameKzNorm !== '') {
                            $structure = OrganizationStructure::whereRaw('LOWER(TRIM(name_ru)) = ?', [mb_strtolower($structureNameKzNorm)])
                                ->orWhereRaw('LOWER(TRIM(name_kz)) = ?', [mb_strtolower($structureNameKzNorm)])
                                ->first();
                        }

                        if (!$structure && ($structureNameRuNorm !== '' || $structureNameKzNorm !== '')) {
                            $structure = OrganizationStructure::create([
                                'name_ru' => $structureNameRuNorm ?: $structureNameKzNorm,
                                'name_kz' => $structureNameKzNorm ?: $structureNameRuNorm,
                            ]);
                        }
                        $structureId = $structure ? $structure->id : null;

                        // Определяем должность
                        $positionNameRuNorm = trim($positionNameRu);
                        $positionNameKzNorm = trim($positionNameKz ?: $positionNameRuNorm);
                        $position = Position::whereRaw('LOWER(TRIM(name_ru)) = ?', [mb_strtolower($positionNameRuNorm)])
                            ->orWhereRaw('LOWER(TRIM(name_kz)) = ?', [mb_strtolower($positionNameRuNorm)])
                            ->first();
                        if (!$position && $positionNameKzNorm !== '') {
                            $position = Position::whereRaw('LOWER(TRIM(name_ru)) = ?', [mb_strtolower($positionNameKzNorm)])
                                ->orWhereRaw('LOWER(TRIM(name_kz)) = ?', [mb_strtolower($positionNameKzNorm)])
                                ->first();
                        }
                        if (!$position) {
                            $position = Position::create([
                                'name_ru' => $positionNameRuNorm,
                                'name_kz' => $positionNameKzNorm,
                            ]);
                        } elseif ($position->name_kz !== $positionNameKzNorm && $positionNameKzNorm !== '') {
                            $position->update(['name_kz' => $positionNameKzNorm]);
                        }

                        // Поиск сотрудника: сначала по табельному номеру, потом по ИИН, затем по full_name
                        $employee = Sotrudniki::where('tabel_nomer', $tabelNumber)->first();
                        if (!$employee) {
                            $employee = Sotrudniki::where('iin', $iin)->first();
                        }
                        if (!$employee && $fullName !== '') {
                            $employee = Sotrudniki::whereRaw('LOWER(TRIM(full_name)) = ?', [mb_strtolower($fullName)])
                                ->first();
                        }

                        $payload = [
                            'full_name' => $fullName,
                            'iin' => $iin,
                            'tabel_nomer' => $tabelNumber,
                            'organization_id' => $structureId,
                            'position_id' => $position->id,
                            'is_imported' => 1,
                        ];

                        if ($employee) {
                            $employee->update($payload);
                        } else {
                            Sotrudniki::create($payload);
                        }

                    } catch (\Exception $e) {
                        $this->failedRows[] = [
                            'row' => ($useHeader ? 2 : 1) + (is_int($index) ? $index : 0),
                            'error' => $e->getMessage(),
                        ];
                    }
                }
            }
        }, $file_name);

        if (!empty($failedRows)) {
            $logPath = storage_path('logs/failed_imports_' . now()->format('Y_m_d_H_i_s') . '.txt');
            file_put_contents($logPath, json_encode($failedRows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $failedCount = count($failedRows);
            Toast::error("Импорт завершён с ошибками ($failedCount строк). Проверьте лог: " . basename($logPath));
        } else {
            Toast::success('Импорт сотрудников завершён успешно.');
        }

        Storage::delete($file_name);
    }

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

        $data = $request->input('structure', []);
        $id = $data['id'] ?? null;

        $payload = [
            'name_ru' => $data['name_ru'],
            'name_kz' => $data['name_kz'],
        ];

        if ($id) {
            $org = OrganizationStructure::find($id);
            if ($org) {
                $org->update($payload);
            } else {
                $org = new OrganizationStructure($payload);
                $org->id = (int)$id;
                $org->save();
            }
        } else {
            OrganizationStructure::create($payload);
        }

        Toast::info('Категория успешно сохранена.');
    }
}
