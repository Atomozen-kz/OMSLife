<?php

namespace App\Orchid\Screens;

use App\Models\Sotrudniki;
use App\Models\TrainingRecord;
use App\Models\TrainingType;
use App\Models\User;
use App\Orchid\Filters\TrainingExpireBoolFilter;
use App\Orchid\Filters\TrainingExpiryFilter;
use App\Orchid\Filters\TrainingOrgFilter;
use App\Orchid\Filters\TrainingTypeFilter;
use App\Orchid\Layouts\TrainingRecordSelection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Layouts\Modal;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class TrainingCenterScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public $trainingTypes;

    public function query(): iterable
    {
        $this->trainingTypes = TrainingType::all();

        $lastYear = Carbon::now()->subYear()->year;

        // Получаем статистику с заполненными данными
        $statistics = TrainingRecord::query()
//            ->whereYear('completion_date', $lastYear)
            ->selectRaw('id_training_type, COUNT(*) as total')
            ->groupBy('id_training_type')
            ->with('trainingType:id,name_ru,name_kz')
            ->get();

        // Преобразуем статистику в формат ключ-значение
        $metrics = $statistics->mapWithKeys(function ($record) {
            return [$record->trainingType->name_ru => $record->total];
        })->toArray();

        // Дополняем метрики нулями для всех типов обучения
        $metricsWithDefaults = $this->trainingTypes->pluck('name_ru')->mapWithKeys(function ($name) use ($metrics) {
            return [$name => $metrics[$name] ?? 0];
        })->toArray();

        // Получаем уникальные ID записей по указанным столбцам
        $uniqueIds = TrainingRecord::select('id')
            ->whereRaw('id = (SELECT MIN(id) FROM training_records as tr
                 WHERE tr.validity_date = training_records.validity_date
                 AND tr.completion_date = training_records.completion_date

                 AND tr.id_sotrudnik = training_records.id_sotrudnik
                 AND tr.id_training_type = training_records.id_training_type)')
            ->filters()
            ->filtersApply([
                TrainingTypeFilter::class,
                TrainingExpiryFilter::class,
                TrainingOrgFilter::class,
                TrainingExpireBoolFilter::class
            ])
            ->pluck('id');


        // Загружаем записи с подгрузкой отношений, исключая дубликаты
        $trainingRecords = TrainingRecord::with(['trainingType', 'sotrudnik'])
            ->whereIn('id', $uniqueIds)
            ->paginate();

        return [
            'trainingRecords' => $trainingRecords,
            'trainingTypes' => $this->trainingTypes,
            'metricsType' => $metricsWithDefaults
        ];
    }


    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Корпоративный учебный центр';
    }

    public function commandBar(): iterable
    {
        $filterValues = request()->query();
        $commandBar = [
            ModalToggle::make('Добавить запись обучения')
                ->modal('addOrUpdateRecordModal')
                ->method('saveRecord')
                ->icon('plus'),
            Link::make('Скачать XLSX')
                ->icon('cloud-download')
                ->route('training.export', $filterValues),
        ];

        if (Auth::user()->hasAccess('platform.training-center-admin')) {
            $commandBar[] = ModalToggle::make('Импорт данных об обучение')
                ->modal('importTrainingModal')
                ->method('importExcel')
                ->icon('database');
            $commandBar[] = ModalToggle::make('Показать типы обучение')
                ->modal('showTypesModal')
                ->icon('book');
        }

        return $commandBar;
    }


    /**
     * The screen's layout elements.
     *
     * @return array
     */
    public function layout(): array
    {
//        $metricsTypeLayout = array();
//        foreach ($this->trainingTypes as $type) {
//            $metricsTypeLayout[] =
//                [
//                    $type->name_ru => 'metricsType.'.$type->id
//                ];
//        }
//        dd($metricsTypeLayout);
        return [
            Layout::view('partials.h5_text', ['text' => 'Статистика обучения за 2024 год']),
            Layout::metrics(
                $this->trainingTypes->pluck('name_ru')->mapWithKeys(function ($name) {
                    return [$name => "metricsType.$name"];
                })->toArray()
            ),

            TrainingRecordSelection::class,


            // Таблица с данными о прохождении обучения
            Layout::table('trainingRecords', [
                TD::make('sotrudnik.fio', 'ФИО')
                    ->render(function (TrainingRecord $record) {
                        return $record->sotrudnik->full_name;
                    }),

                TD::make('type.name_ru', 'Тип обучения')
                    ->render(fn(TrainingRecord $record) => $record->trainingType->name_ru),

                TD::make('certificate_number', 'Номер сертификата'),

                TD::make('protocol_number', 'Номер протокола'),

                TD::make('completion_date', 'Дата прохождения')
                    ->render(fn(TrainingRecord $record) => $record->completion_date->format('d.m.Y')),

                TD::make('validity_date', 'Дата окончение')
                    ->render(fn(TrainingRecord $record) => $record->validity_date->format('d.m.Y')),

                TD::make('left_days', 'Осталось дней')
                    ->render(function (TrainingRecord $record) {
                        if ($record->validity_date->timestamp > now()->timestamp) {
                            // Разница от текущего момента до даты окончания сертификата
                            return now()->diffForHumans($record->validity_date, true);
                        } else {
                            return 'просрочено';
                        }
                    }),

                TD::make('actions', 'Действия')
                    ->align(TD::ALIGN_CENTER)
                    ->render(function (TrainingRecord $record) {
                        return \Orchid\Screen\Fields\Group::make([
                            ModalToggle::make('')
                                ->modal('addOrUpdateRecordModal')
                                ->modalTitle('Редактировать запись обучения')
                                ->method('saveRecord')
                                ->asyncParameters(['record' => $record->id])
                                ->icon('pencil'),

                            Button::make('')
                                ->icon('trash')
                                ->confirm('Вы уверены, что хотите удалить эту запись?')
                                ->method('deleteRecord', ['id' => $record->id]),
                        ])->autoWidth();
                    }),
            ]),

            // Модальное окно для добавления записи
            Layout::modal('addOrUpdateRecordModal', [
                Layout::rows([
                    Input::make('record.id')->type('hidden'),

                    Select::make('record.id_training_type')
                        ->title('Тип обучения')
                        ->fromModel(TrainingType::class, 'name_ru', 'id')
                        ->required(),

                    Relation::make('record.id_sotrudnik')
                        ->fromModel(Sotrudniki::class, 'full_name')
                        ->title('ФИО')
                        ->searchColumns('full_name')
                        ->title('Напишите фамилию/имя/отечество сотрудника')
                        ->required(),

                    Input::make('record.certificate_number')
                        ->title('Номер сертификата'),

                    Input::make('record.protocol_number')
                        ->title('Номер протокола'),

                    Input::make('record.completion_date')
                        ->type('date')
                        ->title('Дата прохождения')
                        ->required(),
                ]),
            ])
                ->async('asyncGetTrainingRecords')
                ->title('Добавить запись о прохождении')
                ->applyButton('Сохранить')
                ->closeButton('Отмена'),

            // Модальное окно для отображения типов обучения
            Layout::modal('showTypesModal', [
                Layout::table('trainingTypes', [
                    TD::make('name_ru', 'Название (RU)'),
//                    TD::make('name_ru', 'Название (RU)'),
                    TD::make('name_kz', 'Название (KZ)'),
                    TD::make('validity_period', 'Срок годности (в месяцах)'),
                    TD::make('type_code', 'type_code'),

                    TD::make('Действия')->render(function (TrainingType $type) {
                        return ModalToggle::make('Редактировать')
                            ->modal('addOrUpdateTypeModal')
                            ->modalTitle('Редактировать тип обучение')
                            ->method('saveType')
                            ->asyncParameters(['type' => $type->id])
                            ->icon('pencil');
                    })
                ]),
                Layout::rows([
                    ModalToggle::make('Добавить тип обучение')
                        ->modal('addOrUpdateTypeModal')
                        ->modalTitle('Добавить тип обучение')
                        ->method('saveType')
                        ->icon('plus'),
                ]),
            ])->size(Modal::SIZE_LG)
                ->withoutApplyButton()
                ->title('Типы обучения')
                ->applyButton('Закрыть'),

            // Модальное окно для добавления типа обучения
            Layout::modal('addOrUpdateTypeModal', [
                Layout::rows([

                    Input::make('type.id')->type('hidden'),

                    Input::make('type.name_ru')
                        ->title('Название (RU)')
                        ->required(),

                    Input::make('type.name_kz')
                        ->title('Название (KZ)')
                        ->required(),

                    Input::make('type.validity_period')
                        ->title('Срок годности (в месяцах)')
                        ->type('number')
                        ->required(),

                    Input::make('type.type_code')
                        ->title('type_code')
                        ->required(),
                ]),
            ])
                ->async('asyncGetTrainingType')
                ->title('Добавить тип обучения')
                ->applyButton('Сохранить')
                ->closeButton('Отмена'),

            //Модалное окно для импорта данных
            Layout::modal('importTrainingModal', [
                Layout::rows([
                    Select::make('training_type_id')
                        ->title('Тип обучения')
                        ->fromModel(TrainingType::class, 'name_ru', 'id')
                        ->required(),

                    Input::make('file')
                        ->type('file')
                        ->title('Выберите файл')
                        ->required(),
                ]),
            ])
                ->title('Импорт данных о прохождении обучения')
                ->applyButton('Импортировать')
                ->closeButton('Отмена')
        ];
    }

    public function asyncGetTrainingType(TrainingType $type)
    {
        return [
            'type' => $type,
        ];
    }

    public function asyncGetTrainingRecords(TrainingRecord $record)
    {
        return [
            'record' => $record,
        ];
    }

    /**
     * Save a training record.
     */
    public function saveRecord(Request $request)
    {
        $data = $request->validate([
            'record.id' => 'nullable|integer',
            'record.id_training_type' => 'required|exists:training_types,id',
            'record.id_sotrudnik' => 'required|exists:sotrudniki,id',
            'record.certificate_number' => 'nullable|string',
            'record.protocol_number' => 'nullable|string',
            'record.completion_date' => 'required|date',
        ]);

        $completionDate = $data['record']['completion_date'];
        $validityPeriod = TrainingType::find($data['record']['id_training_type'])->validity_period;
        $recordId = $data['record']['id'] ?? null;

        $recordData = [
            'id_training_type' => $data['record']['id_training_type'],
            'id_sotrudnik' => $data['record']['id_sotrudnik'],
            'certificate_number' => $data['record']['certificate_number'] ?? null,
            'protocol_number' => $data['record']['protocol_number'] ?? null,
            'completion_date' => $completionDate,
            'validity_date' => now()->parse($completionDate)->addMonths($validityPeriod),
        ];

        if ($recordId) {
            $record = TrainingRecord::find($recordId);
            if ($record) {
                $record->update($recordData);
                Toast::info('Запись успешно обновлена.');
            } else {
                TrainingRecord::create($recordData);
                Toast::info('Запись успешно добавлена.');
            }
        } else {
            TrainingRecord::create($recordData);
            Toast::info('Запись успешно добавлена.');
        }
    }

    /**
     * Delete a training record.
     */
    public function deleteRecord(Request $request)
    {
        $id = $request->get('id');
        $record = TrainingRecord::find($id);

        if ($record) {
            $record->delete();
            Toast::info('Запись успешно удалена.');
        } else {
            Toast::error('Запись не найдена.');
        }
    }

    /**
     * Save a training type.
     */
    public function saveType(Request $request)
    {
        $data = $request->validate([
            'type.id' => 'nullable|integer',
            'type.name_ru' => 'required|string',
            'type.name_kz' => 'required|string',
            'type.validity_period' => 'required|integer',
            'type.type_code' => 'required|string',
        ]);

        $typeAttrs = $data['type'];
        $typeId = $typeAttrs['id'] ?? null;
        // не передаём id в атрибутах (может быть guarded)
        unset($typeAttrs['id']);

        if ($typeId) {
            $type = TrainingType::find($typeId);
            if ($type) {
                $type->update($typeAttrs);
            } else {
                TrainingType::create($typeAttrs);
            }
        } else {
            TrainingType::create($typeAttrs);
        }

        Toast::info('Тип обучения успешно добавлен.');
    }

    public function importExcel(Request $request)
    {
        $request->validate([
            'training_type_id' => 'required|exists:training_types,id',
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $file = $request->file('file');
        $filePath = $file->getRealPath();

        // Загружаем файл Excel
        $spreadsheet = IOFactory::load($filePath);
        // Попытаемся автоматически найти лист с данными: ищем первую непустую ячейку в колонке B в первых 50 строк
        $sheet = null;
        $sheetCount = $spreadsheet->getSheetCount();
        $startRowProbe = 5;
        $probeRows = 50;
        for ($si = 0; $si < $sheetCount; $si++) {
            $s = $spreadsheet->getSheet($si);
            for ($r = $startRowProbe; $r < $startRowProbe + $probeRows; $r++) {
                $cell = $s->getCellByColumnAndRow(2, $r);
                $val = $cell ? (string)$cell->getCalculatedValue() : '';
                if (trim($val) !== '') {
                    $sheet = $s;
                    break 2;
                }
            }
        }
        // Если не нашли — используем первый лист как fallback
        if ($sheet === null) {
            $sheet = $spreadsheet->getSheet(0);
        }

        $trainingTypeId = $request->input('training_type_id');
        $validatePeriodMonths = TrainingType::find($trainingTypeId)->validity_period;

        $failedRows = [];

        // Начинаем обработку строк
        // Определяем реальную стартовую строку: ищем первую непустую в колонках B/I/J
        $highestRow = (int)$sheet->getHighestRow();
        $detectedStart = null;
        $probeLimit = min($highestRow, 200); // не сканируем слишком глубоко
        for ($r = 1; $r <= $probeLimit; $r++) {
            $v2 = trim((string)$sheet->getCellByColumnAndRow(2, $r)->getCalculatedValue());
            $v9 = trim((string)$sheet->getCellByColumnAndRow(9, $r)->getCalculatedValue());
            $v10 = trim((string)$sheet->getCellByColumnAndRow(10, $r)->getCalculatedValue());
            if ($v2 !== '' || $v9 !== '' || $v10 !== '') {
                $detectedStart = $r;
                break;
            }
        }
        $startRow = $detectedStart ?? 5; // fallback на 5
        $naideno = 0;
        $ne_naideno = 0;
        $parsedRows = 0;
        $samples = [];

        for ($row = $startRow; $row <= $highestRow; $row++) {
            try {
                // Читаем данные из ячеек (используем явную нумерацию столбцов: 2=B, 9=I, 10=J)
                $fioCell = $sheet->getCellByColumnAndRow(2, $row);
                $completionCell = $sheet->getCellByColumnAndRow(9, $row);
                $protocolCell = $sheet->getCellByColumnAndRow(10, $row);

                // Получаем вычисленное значение (формулы) и поддерживаем RichText
                $fio = $fioCell ? $fioCell->getCalculatedValue() : null;
                if ($fio instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                    $fio = $fio->getPlainText();
                }

                $completionDate = $completionCell ? $completionCell->getCalculatedValue() : null;
                if ($completionDate instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                    $completionDate = $completionDate->getPlainText();
                }

                $protocolNumber = $protocolCell ? $protocolCell->getCalculatedValue() : null;
                if ($protocolNumber instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                    $protocolNumber = $protocolNumber->getPlainText();
                }

                // Если строка полностью пустая — пропускаем
                if (empty(trim((string)$fio)) && empty(trim((string)$completionDate)) && empty(trim((string)$protocolNumber))) {
                    continue;
                }

                $parsedRows++;
                if (count($samples) < 10) {
                    $samples[] = ['row' => $row, 'fio' => $fio, 'completion' => $completionDate, 'protocol' => $protocolNumber];
                }

                // Разделяем ФИО
                $fioParts = preg_split('/\s+/', trim((string)$fio));
                $lastName = $fioParts[0] ?? null;
                $firstName = $fioParts[1] ?? null;
                $fatherName = $fioParts[2] ?? null;

                // Преобразуем дату
                $parsedDate = null;
                if (is_numeric($completionDate)) {
                    $parsedDate = Date::excelToDateTimeObject($completionDate)->format('Y-m-d');
                } else {
                    $dateStr = trim((string)$completionDate);
                    if ($dateStr !== '') {
                        // Попытка парсинга формата d.m.Y или Y-m-d и общих строковых дат
                        try {
                            if (preg_match('/^\d{1,2}\.\d{1,2}\.\d{2,4}$/', $dateStr)) {
                                $dt = \Carbon\Carbon::createFromFormat('d.m.Y', $dateStr);
                                $parsedDate = $dt ? $dt->format('Y-m-d') : null;
                            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
                                $parsedDate = $dateStr;
                            } else {
                                $ts = strtotime($dateStr);
                                if ($ts !== false && $ts > 0) {
                                    $parsedDate = date('Y-m-d', $ts);
                                }
                            }
                        } catch (\Exception $ex) {
                            $parsedDate = null;
                        }
                     }
                 }

                if (!$parsedDate) {
                    $failedRows[] = [
                        'row' => $row,
                        'error' => 'Неверный формат даты',
                        'raw' => ['fio' => $fio, 'completion' => $completionDate],
                    ];
                    continue;
                }

                // Проверяем сотрудника
                // Нормализуем ФИО для поиска: trim + mb_strtolower
                // Собираем полное имя для поиска
                $fullNameSearch = trim("$lastName $firstName $fatherName");
                $fullNameSearchLower = mb_strtolower($fullNameSearch);

                // Пытаемся найти точное совпадение
                $employee = Sotrudniki::whereRaw('LOWER(TRIM(full_name)) = ?', [$fullNameSearchLower])->first();

                // Если не нашли, попробуем по частичному совпадению
                if (!$employee) {
                    $employee = Sotrudniki::whereRaw('LOWER(TRIM(full_name)) LIKE ?', ["%{$fullNameSearchLower}%"])->first();
                }

                if ($employee) {
                    $naideno++;
                    // Добавляем запись в training_records
                    TrainingRecord::updateOrCreate([
                        'id_training_type' => $trainingTypeId,
                        'id_sotrudnik' => $employee->id,
                        'completion_date' => $parsedDate,
                    ], [
                        'validity_date' => now()->parse($parsedDate)->addMonths($validatePeriodMonths),
                        //'certificate_number' => $certificateNumber,
                        'protocol_number' => $protocolNumber,
                    ]);
                } else {
                    $ne_naideno++;
                    // Добавляем запись в training_records_import
                    DB::table('training_records_import')->insert([
                        'last_name' => $lastName,
                        'first_name' => $firstName,
                        'father_name' => $fatherName,
                        //'certificate_number' => $certificateNumber,
                        'protocol_number' => $protocolNumber,
                        'completion_date' => $parsedDate,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                $failedRows[] = [
                    'row' => $row,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Сохранение логов ошибок и итогов импорта в отдельный файл
        $logData = [
            'timestamp'    => now()->toDateTimeString(),
            'found'        => $naideno,
            'not_found'    => $ne_naideno,
            'failed_rows'  => $failedRows,
            'parsed_rows'  => $parsedRows,
            'samples'      => $samples,
        ];

        $logPath = storage_path('logs/TrainingImportLog_' . now()->format('Y_m_d_H_i_s') . '.txt');
        file_put_contents($logPath, json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if (!empty($failedRows)) {
            Alert::error('Импорт завершен с ошибками. Проверьте лог: ' . $logPath . '. Ошибка в строках: ' . implode(', ', array_column($failedRows, 'row')));
        } else {
            Alert::success('Импорт успешен. Найдено ' . $naideno . ' сотрудников, не найдено ' . $ne_naideno . ' сотрудников. Лог: ' . $logPath);
        }
    }

    public function exportExcel(Request $request)
    {
        $filterParams = $request->input('filters', []);

        // Получаем записи с применением фильтров и подгруженными связями
        $trainingRecords = \App\Models\TrainingRecord::filters($filterParams)
            ->filtersApply([
                TrainingTypeFilter::class,
                TrainingExpiryFilter::class,
                TrainingOrgFilter::class,
                TrainingExpireBoolFilter::class
            ])
            ->with(['trainingType', 'sotrudnik'])
            ->get();

        // Удаляем дубликаты по набору полей
        $uniqueRecords = $trainingRecords->unique(function ($record) {
            return
                $record->validity_date->format('Y-m-d') . '_' .
                $record->completion_date->format('Y-m-d') . '_' .
                $record->certificate_number . '_' .
                $record->protocol_number . '_' .
                $record->id_sotrudnik . '_' .
                $record->id_training_type;
        });

        // Формируем массив данных для экспорта
        $data = $uniqueRecords->map(function ($record) {

            if ($record->validity_date->timestamp > now()->timestamp) {
                $left = now()->diffForHumans($record->validity_date, true);
            } else {
                $left = 'просрочено';
            }

            return [
                'ФИО'                => $record->sotrudnik->full_name,
                'Тип обучения'       => $record->trainingType->name_ru,
                'Номер сертификата'  => $record->certificate_number,
                'Номер протокола'    => $record->protocol_number,
                'Дата прохождения'   => $record->completion_date->format('d.m.Y'),
                'Дата окончания'     => $record->validity_date->format('d.m.Y'),
                'Осталось дней'      => $left,
            ];
        })->toArray();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\TrainingRecordArrayExport($data),
            'training_records.xlsx'
        );
    }

}

