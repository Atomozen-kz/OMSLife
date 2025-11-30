<?php

namespace App\Orchid\Screens;

use App\Models\FinancialAssistanceRequest;
use App\Models\FinancialAssistanceType;
use App\Models\FinancialAssistanceSigner;
use App\Models\User;
use Illuminate\Http\Request;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\Input;
use Orchid\Support\Facades\Layout;
use Orchid\Filters\Filter;
use Orchid\Support\Facades\Alert;
use Orchid\Screen\Layouts\Modal;

class FinancialAssistanceRequestListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        $query = FinancialAssistanceRequest::with(['sotrudnik', 'assistanceType', 'signer'])
            ->filters()
            ->defaultSort('created_at', 'desc');

        return [
            'requests' => $query->paginate(20),
            'total_count' => FinancialAssistanceRequest::count(),
            'pending_count' => FinancialAssistanceRequest::where('status', 1)->count(),
            'approved_count' => FinancialAssistanceRequest::where('status', 2)->count(),
            'rejected_count' => FinancialAssistanceRequest::where('status', 3)->count(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Заявки на материальную помощь';
    }

    /**
     * The description is displayed on the user's screen under the heading
     */
    public function description(): ?string
    {
        return 'Управление и обработка заявок на материальную помощь';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Button::make('Экспорт в Excel')
                ->icon('cloud-download')
                ->method('exportToExcel')
                ->class('btn btn-outline-secondary'),
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
            // Статистика
            Layout::view('orchid.financial-assistance-stats', [
                'total_count' => $this->query()['total_count'],
                'pending_count' => $this->query()['pending_count'],
                'approved_count' => $this->query()['approved_count'],
                'rejected_count' => $this->query()['rejected_count'],
            ]),

            // Фильтры
            Layout::rows([
                Select::make('filter.status')
                    ->title('Статус')
                    ->empty('Все статусы')
                    ->options([
                        1 => 'На рассмотрении',
                        2 => 'Одобрено',
                        3 => 'Отклонено',
                    ])
                    ->value(request('filter.status')),

                Select::make('filter.type')
                    ->title('Тип материальной помощи')
                    ->empty('Все типы')
                    ->fromModel(FinancialAssistanceType::class, 'name')
                    ->value(request('filter.type')),

                Input::make('filter.search')
                    ->title('Поиск по заявителю')
                    ->placeholder('Введите ФИО или ID заявителя')
                    ->value(request('filter.search')),
            ])->title('Фильтры'),

            // Основная таблица
            Layout::table('requests', [
                TD::make('id', 'ID')
                    ->sort()
                    ->render(function (FinancialAssistanceRequest $request) {
                        return Link::make('#' . $request->id)
                            ->route('platform.financial-assistance.request.view', $request->id);
                    }),

                TD::make('sotrudnik', 'Заявитель')
                    ->render(function (FinancialAssistanceRequest $request) {
                        $name = $request->sotrudnik ? trim($request->sotrudnik->last_name . ' ' . $request->sotrudnik->first_name . ' ' . $request->sotrudnik->father_name) : 'Не указано';
                        $position = $request->sotrudnik->position->name_ru ?? '';
                        $organization = $request->sotrudnik->organization->name_ru ?? '';
                        return "<strong>{$name}</strong>" . ($position ? "<br><small class='text-muted'>{$position}</small><br><small class='text-muted'>{$organization}</small>" : '');
                    })
                    ->width('200px'),

                TD::make('assistanceType', 'Тип помощи')
                    ->render(function (FinancialAssistanceRequest $request) {
                        return $request->assistanceType->name ?? 'Не указано';
                    })
                    ->width('150px'),

                // TD::make('form_data', 'Детали заявления')
                //     ->render(function (FinancialAssistanceRequest $request) {
                //         if (empty($request->form_data)) {
                //             return '<span class="text-muted">Нет данных</span>';
                //         }

                //         $details = [];
                //         foreach ($request->form_data as $key => $value) {
                //             if (strlen($value) > 30) {
                //                 $value = substr($value, 0, 30) . '...';
                //             }
                //             $details[] = "<strong>{$key}:</strong> {$value}";
                //         }

                //         return implode('<br>', array_slice($details, 0, 2));
                //     })
                //     ->width('200px'),

                TD::make('status', 'Статус')
                    ->render(function (FinancialAssistanceRequest $request) {
                        $statusClass = match($request->status) {
                            1 => 'badge-warning',
                            2 => 'badge-success',
                            3 => 'badge-danger',
                            default => 'badge-secondary'
                        };
                        return '<span class="bg-primary badge '.$statusClass.'">'.$request->status_name.'</span>';
                    })
                    ->sort()
                    ->width('120px'),

                TD::make('submitted_at', 'Дата подачи')
                    ->render(function (FinancialAssistanceRequest $request) {
                        return $request->submitted_at ? $request->submitted_at->format('d.m.Y H:i') : 'Не указана';
                    })
                    ->sort()
                    ->width('120px'),

                TD::make('processed_at', 'Дата рассмотрения')
                    ->render(function (FinancialAssistanceRequest $request) {
                        return $request->processed_at ? $request->processed_at->format('d.m.Y H:i') : '-';
                    })
                    ->width('120px'),

                TD::make('signer', 'Подписант')
                    ->render(function (FinancialAssistanceRequest $request) {
                        return $request->signer ? $request->signer->full_name : '-';
                    })
                    ->width('150px'),

                TD::make('actions', 'Действия')
                    ->render(function (FinancialAssistanceRequest $request) {
                        return Link::make('Детали')
                        ->icon('eye')
                        ->route('platform.financial-assistance.request.view', $request->id);

                         return DropDown::make('Действия')
                            ->icon('bs.three-dots-vertical')
                            ->list([
                                Link::make('Детали')
                                    ->icon('eye')
                                    ->route('platform.financial-assistance.request.view', $request->id),

                                Link::make('HTML превью')
                                    ->icon('monitor')
                                    ->route('platform.financial-assistance.request.html', $request->id)
                                    ->target('_blank'),

                                ModalToggle::make('Обработать')
                                    ->icon('pencil')
                                    ->modal('processRequestModal')
                                    ->modalTitle('Обработка заявки #' . $request->id)
                                    ->method('processRequest')
                                    ->parameters(['request_id' => $request->id])
                                    ->canSee($request->status == 1),

                                Button::make('Одобрить')
                                    ->icon('check')
                                    ->method('approveRequest')
                                    ->parameters(['request_id' => $request->id])
                                    ->confirm('Вы уверены, что хотите одобрить эту заявку?')
                                    ->class('btn btn-sm btn-success')
                                    ->canSee($request->status == 1),

                                Button::make('Отклонить')
                                    ->icon('close')
                                    ->method('rejectRequest')
                                    ->parameters(['request_id' => $request->id])
                                    ->confirm('Вы уверены, что хотите отклонить эту заявку?')
                                    ->class('btn btn-sm btn-danger')
                                    ->canSee($request->status == 1),
                            ]);
                    })
                    ->width('120px'),
            ]),

            // Модальное окно для обработки заявки
            Layout::modal('processRequestModal', [
                Layout::rows([
                    Select::make('status')
                        ->title('Новый статус')
                        ->options([
                            2 => 'Одобрить',
                            3 => 'Отклонить',
                        ])
                        ->required(),

                    Select::make('signer_id')
                        ->title('Подписант')
                        ->fromModel(FinancialAssistanceSigner::class, 'full_name')
                        ->empty('Выберите подписанта')
                        ->help('Выберите ответственного за подписание решения'),

                    TextArea::make('comment')
                        ->title('Комментарий')
                        ->placeholder('Укажите причину решения или дополнительные комментарии')
                        ->rows(4),

                    Input::make('request_id')
                        ->type('hidden'),
                ]),
            ])
            ->title('Обработка заявки')
            ->applyButton('Сохранить решение'),
        ];
    }

    /**
     * Обработка заявки
     */
    public function processRequest(Request $request)
    {
        $requestId = $request->get('request_id');
        $status = $request->get('status');
        $signerId = $request->get('signer_id');
        $comment = $request->get('comment');

        $assistanceRequest = FinancialAssistanceRequest::findOrFail($requestId);

        $assistanceRequest->update([
            'status' => $status,
            'id_signer' => $signerId,
            'comment' => $comment,
            'processed_at' => now(),
        ]);

        // Создаем запись в истории статусов
        $assistanceRequest->statusHistory()->create([
            'new_status' => $status,
            'id_user' => auth()->id(),
            'comment' => $comment,
        ]);

        $statusName = $status == 2 ? 'одобрена' : 'отклонена';
        Alert::info("Заявка #{$requestId} была {$statusName}.");
    }

    /**
     * Быстрое одобрение заявки
     */
    public function approveRequest(Request $request)
    {
        $requestId = $request->get('request_id');
        $assistanceRequest = FinancialAssistanceRequest::findOrFail($requestId);

        $assistanceRequest->update([
            'status' => 2,
            'processed_at' => now(),
        ]);

        $assistanceRequest->statusHistory()->create([
            'new_status' => 2,
            'id_user' => auth()->id(),
            'comment' => 'Быстрое одобрение',
        ]);

        Alert::info("Заявка #{$requestId} одобрена.");
    }

    /**
     * Быстрое отклонение заявки
     */
    public function rejectRequest(Request $request)
    {
        $requestId = $request->get('request_id');
        $assistanceRequest = FinancialAssistanceRequest::findOrFail($requestId);

        $assistanceRequest->update([
            'status' => 3,
            'processed_at' => now(),
        ]);

        $assistanceRequest->statusHistory()->create([
            'new_status' => 3,
            'id_user' => auth()->id(),
            'comment' => 'Быстрое отклонение',
        ]);

        Alert::info("Заявка #{$requestId} отклонена.");
    }

    /**
     * Экспорт в Excel
     */
    public function exportToExcel()
    {
        Alert::info('Функция экспорта будет реализована позже.');
    }
}
