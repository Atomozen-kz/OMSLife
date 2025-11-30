<?php

namespace App\Orchid\Screens;

use App\Models\OrganizationStructure;
use App\Models\Sotrudniki;
use App\Models\SotrudnikiCodes;
use App\Models\TrainingRecord;
use App\Models\PayrollSlip;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Sight;
use Illuminate\Support\Facades\Storage;
use Orchid\Screen\Actions\ModalToggle;
use Illuminate\Http\Request;
use Orchid\Support\Facades\Toast;
use App\Models\TrainingType;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\DateTimer;

class OneSotrudnikScreen extends Screen
{
    public $sotrudnik;

    /**
     * Query data.
     *
     * @param Sotrudniki $sotrudnik
     * @return array
     */
    public function query(Sotrudniki $sotrudnik): array
    {
        $this->sotrudnik = $sotrudnik;

//dd(SotrudnikiCodes::where('sotrudnik_id', $sotrudnik->id)->get(), TrainingRecord::where('id_sotrudnik', $sotrudnik->id)
//    ->with('trainingType')
//    ->orderBy('completion_date', 'desc')
//    ->get());
        return [
            'sotrudnik' => $sotrudnik,
            'training_records' => TrainingRecord::where('id_sotrudnik', $sotrudnik->id)
                ->with('trainingType')
                ->orderBy('completion_date', 'desc')
                ->get(),
            'sotrudniki_codes' => SotrudnikiCodes::where('sotrudnik_id', $sotrudnik->id)->get(),
            'payroll_slips' => PayrollSlip::select(['id', 'month', 'created_at', 'is_read'])->where('sotrudniki_id', $sotrudnik->id)
                ->orderByDesc('created_at')
                ->get(),
        ];
    }

    /**
     * Display header name.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->sotrudnik->last_name . ' ' .
               $this->sotrudnik->first_name . ' ' .
               $this->sotrudnik->father_name;
    }

    /**
     * Display header description.
     *
     * @return string|null
     */
    public function description(): ?string
    {
        return 'Информация о сотруднике';
    }

    /**
     * Button commands.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        $buttons = [
            Link::make('Назад к списку')
                ->icon('arrow-left')
                ->route('platform.sotrudniki'),
        ];

        // Добавить кнопку Добавить/Обновить код молока в commandBar
//        if ($this->sotrudnik) {
//            $hasMilkCode = SotrudnikiCodes::where('sotrudnik_id', $this->sotrudnik->id)->where('type', 'milk')->exists();
//
//            if ($hasMilkCode) {
//                $buttons[] = ModalToggle::make('Обновить код молока')
//                    ->modal('updateMilkCodeModal')
//                    ->method('saveMilkCode')
//                    ->async('asyncMilkCode')
//                    ->icon('note');
//            } else {
//                $buttons[] = ModalToggle::make('Добавить код молока')
//                    ->modal('updateMilkCodeModal')
//                    ->method('saveMilkCode')
//                    ->async('asyncMilkCode')
//                    ->asyncParameters(['sotrudnik_id' => $this->sotrudnik->id])
//                    ->icon('plus');
//            }
//        }

        return $buttons;
    }

    /**
     * Views.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            Layout::legend('sotrudnik', [
                Sight::make('last_name', 'Фамилия'),
                Sight::make('first_name', 'Имя'),
                Sight::make('father_name', 'Отчество'),
                Sight::make('tabel_nomer', 'Табельный номер'),
                Sight::make('iin', 'ИИН'),
                Sight::make('phone_number', 'Номер телефона'),
                Sight::make('psp_name', 'ПСП')->render(function ($sotrudnik) {

                    $psp = OrganizationStructure::find( $sotrudnik->organization_id)->getFirstParent();

                    return $psp->name_kz.' | '.$psp->name_ru;
                }),
                Sight::make('organization.name_ru', 'Организация')->render(function ($sotrudnik) {
                    return $sotrudnik->organization ? $sotrudnik->organization->name_kz.' | '.$sotrudnik->organization->name_ru : '';
                }),
                Sight::make('position.name_ru', 'Должность')->render(function ($sotrudnik) {
                    return $sotrudnik->position ? $sotrudnik->position->name_kz.' | '.$sotrudnik->position->name_ru : '';
                }),
            ])->title('Основная информация'),

            // Таблица истории обучения с действиями
            // Кнопка добавления новой записи обучения (отображается над таблицей)
            Layout::rows([
                Group::make([
                    ModalToggle::make('Добавить запись обучения')
                        ->modal('updateTrainingModal')
                        ->modalTitle('Добавить запись обучения')
                        ->method('saveTrainingRecord')
                        ->async('asyncTrainingRecord')
                        ->asyncParameters(['training_id' => null])
                        ->type(Color::SUCCESS)
                        ->icon('plus'),
                ]),
            ]),

             Layout::table('training_records', [
                TD::make('trainingType.name', 'Тип обучения')->render(function ($sotrudnik) {
                    return $sotrudnik->trainingType ? $sotrudnik->trainingType->name_kz . ' | ' . $sotrudnik->trainingType->name_ru : '';
                })
                    ->width('200px'),
                TD::make('completion_date', 'Дата завершения')
                    ->render(fn ($record) => date('d.m.Y', strtotime($record->completion_date)))
                    ->width('150px'),
                TD::make('validity_date', 'Действительно до')
                    ->render(fn ($record) => date('d.m.Y', strtotime($record->validity_date)))
                    ->width('150px'),
                TD::make('certificate_number', 'Номер сертификата'),
                TD::make('protocol_number', 'Номер протокола'),
                TD::make('Действия')->render(function ($record) {
                    return Group::make([
//                        ModalToggle::make('Редактировать')
//                            ->modal('updateTrainingModal')
//                            ->modalTitle('Добавить / редактировать запись обучения')
//                            ->method('saveTrainingRecord')
//                            ->asyncParameters(['training_id' => $record->id])
//                            ->async('asyncTrainingRecord')
//                            ->icon('pencil'),
                        Button::make('Удалить')
                            ->icon('trash')
                            ->confirm('Удалить запись обучения?')
                            ->type(Color::DANGER)
                            ->method('deleteTrainingRecord')

                            ->parameters(['id' => $record->id]),
                    ]);
                })->width('160px'),
            ])->title('История обучения'),

            // Модальное окно для добавления / редактирования записи обучения
            Layout::modal('updateTrainingModal', [
                Layout::rows([
                    Input::make('training.id')->type('hidden'),

                    Select::make('training.id_training_type')
                        ->title('Тип обучения')
                        ->fromModel(TrainingType::class, 'name_ru', 'id')
                        ->required(),

                    DateTimer::make('training.completion_date')
                        ->title('Дата прохождения')
                        ->format('Y-m-d')
                        ->required(),

                    Input::make('training.certificate_number')
                        ->title('Номер сертификата')
                        ->placeholder('Опционально'),

                    Input::make('training.protocol_number')
                        ->title('Номер протокола')
                        ->placeholder('Опционально'),
                ]),
            ])->async('asyncTrainingRecord')
              ->title('Добавить / редактировать запись обучения')
              ->applyButton('Сохранить')
              ->closeButton('Отмена'),


            ($this->sotrudnik && SotrudnikiCodes::where('sotrudnik_id', $this->sotrudnik->id)->exists())
            ? Layout::table('sotrudniki_codes', [
                TD::make('code', 'Код молока'),
                TD::make('type', 'Тип'),
                TD::make('actions', 'Действия')->render(function ($code) {
                    return ModalToggle::make('Обновить')
                        ->modal('updateMilkCodeModal')
                        ->method('saveMilkCode')
                        ->async('asyncMilkCode')
                        ->icon('pencil')
                        ->type(Color::WARNING)
                        ->asyncParameters(['sotrudnik_id' => $code->sotrudnik_id]);
                })->width('120px'),
            ])->title('Коды для молока')
            : Layout::rows([
                Group::make([
                    ModalToggle::make('Добавить код молока')
                        ->modal('updateMilkCodeModal')
                        ->method('saveMilkCode')
                        ->async('asyncMilkCode')
                        ->asyncParameters(['sotrudnik_id' => $this->sotrudnik ? $this->sotrudnik->id : null])
                        ->icon('plus'),
                ]),
            ])->title('Коды для молока'),

            // Модальное окно для добавления / обновления кода молока
            Layout::modal('updateMilkCodeModal', [
                Layout::rows([
                    // скрытый идентификатор сотрудника
                    Input::make('sotrudnik_id')
                        ->type('hidden'),

                    // Отображаем ФИО сотрудника (readonly) - используем отдельный ключ modal_sotrudnik, чтобы
                    // не перезаписывать основной 'sotrudnik', который нужен для Layout::legend
                    Input::make('fio')
                        ->title('Сотрудник')
                        ->readonly(),

                    // Тип (readonly, всегда milk)
                    Input::make('type')
                        ->title('Тип')
                        ->readonly(),

                    // Поле для ввода кода
                    Input::make('code')
                        ->title('Код')
                        ->placeholder('Введите код'),
                ]),
            ])->title('Код для молока')
              ->async('asyncMilkCode')
              ->applyButton('Сохранить')
              ->closeButton('Отмена'),

            Layout::table('payroll_slips', [
                TD::make('month', 'Месяц')
                    ->render(fn ($p) => e($p->month)),

                TD::make('created_at', 'Дата создания')
                    ->render(fn ($p) => $p->created_at ? date('d.m.Y H:i', strtotime($p->created_at)) : '-'),

                TD::make('is_read', 'Прочитан')
                    ->render(function ($p) {
                        return $p->is_read ? 'Да' : 'Нет';
                    }),
            ])->title('Расчетные листы'),
        ];
    }

    /**
     * Async загрузка данных для модального окна добавления/обновления кода молока
     *
     * @param SotrudnikiCodes|null $sotrudniki_code
     * @return array
     */
    public function asyncMilkCode($sotrudnik_id): array
    {

        $sotrudnik = Sotrudniki::find($sotrudnik_id);
        $sotrudniki_code = SotrudnikiCodes::where('sotrudnik_id', $sotrudnik->id)->pluck('code')->first() ?? null;

        return array(
            'sotrudnik_id' => $sotrudnik->id ?? null,
            'fio' => $sotrudnik ? ($sotrudnik->last_name . ' ' . $sotrudnik->first_name . ' ' . $sotrudnik->father_name) : '',
            'type' => 'milk',
            'code' => $sotrudniki_code,
        );
    }

    /**
     * Сохранение кода молока (create / update)
     *
     * @param Request $request
     */
    public function saveMilkCode(Request $request)
    {
        $request->validate([
            'sotrudnik_id' => 'required|exists:sotrudniki,id',
            'code' => 'required|string|max:255',
        ]);

        $data = $request->all();

        $sotrudnikId = $data['sotrudnik_id'];
        $codeValue = $data['code'];

        $record = SotrudnikiCodes::where('sotrudnik_id', $sotrudnikId)->where('type', 'milk')->first();

        if ($record) {
            $record->update(['code' => $codeValue]);
            Toast::info('Код молока обновлён');
        } else {
            SotrudnikiCodes::create([
                'sotrudnik_id' => $sotrudnikId,
                'type' => 'milk',
                'code' => $codeValue,
            ]);
            Toast::info('Код молока добавлен');
        }

        return back();
    }

    /**
     * Async загрузка данных записи обучения для модального окна
     *
     * @param int|null $training_id
     * @return array
     */
    public function asyncTrainingRecord($training_id = null): array
    {
        if ($training_id) {
            $r = TrainingRecord::find($training_id);
            if ($r) {
                return [
                    'training' => [
                        'id' => $r->id,
                        'id_training_type' => $r->id_training_type,
                        'completion_date' => $r->completion_date ? $r->completion_date->format('Y-m-d') : null,
                        'certificate_number' => $r->certificate_number,
                        'protocol_number' => $r->protocol_number,
                    ],
                ];
            }
        }

        // пустые значения для создания новой записи
        return [
            'training' => [
                'id' => null,
                'id_training_type' => null,
                'completion_date' => null,
                'certificate_number' => null,
                'protocol_number' => null,
            ],
        ];
    }

    /**
     * Сохранение (create / update) записи обучения
     *
     * @param Request $request
     */
    public function saveTrainingRecord(Request $request)
    {
        $data = $request->validate([
            'training.id' => 'nullable|integer|exists:training_records,id',
            'training.id_training_type' => 'required|exists:training_types,id',
            'training.completion_date' => 'required|date',
            'training.certificate_number' => 'nullable|string',
            'training.protocol_number' => 'nullable|string',
        ]);

        $t = $data['training'];
        $validityMonths = TrainingType::find($t['id_training_type'])->validity_period ?? 0;
        $validityDate = now()->parse($t['completion_date'])->addMonths($validityMonths);

        if (!empty($t['id'])) {
            // обновление
            $record = TrainingRecord::find($t['id']);
            if ($record) {
                $record->update([
                    'id_training_type' => $t['id_training_type'],
                    'completion_date' => $t['completion_date'],
                    'validity_date' => $validityDate,
                    'certificate_number' => $t['certificate_number'] ?? null,
                    'protocol_number' => $t['protocol_number'] ?? null,
                ]);
            }
             Toast::info('Запись обучения обновлена.');
        } else {
            // создание
            TrainingRecord::create([
                'id_training_type' => $t['id_training_type'],
                'id_sotrudnik' => $this->sotrudnik->id,
                'completion_date' => $t['completion_date'],
                'validity_date' => $validityDate,
                'certificate_number' => $t['certificate_number'] ?? null,
                'protocol_number' => $t['protocol_number'] ?? null,
            ]);
            Toast::info('Запись обучения добавлена.');
        }

        return back();
    }

    /**
     * Удаление записи обучения
     *
     * @param Request $request
     */
    public function deleteTrainingRecord(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:training_records,id',
        ]);

        TrainingRecord::where('id', $data['id'])->delete();

        Toast::info('Запись обучения удалена.');

        return back();
    }
}
