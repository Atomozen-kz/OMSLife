<?php

namespace App\Orchid\Screens;

use App\Models\FinancialAssistanceRequest;
use App\Models\FinancialAssistanceSigner;
use App\Services\FinancialAssistancePdfService;
use Illuminate\Http\Request;
use Orchid\Screen\Screen;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\Input;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Alert;

class FinancialAssistanceRequestViewScreen extends Screen
{
    /**
     * @var FinancialAssistanceRequest
     */
    public $request;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(FinancialAssistanceRequest $request): iterable
    {
        $this->request = $request;

        return [
            'request' => $request->load(['sotrudnik', 'assistanceType', 'signer', 'statusHistory.changedBy']),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Заявка на материальную помощь #' . $this->request->id;
    }

    /**
     * The description is displayed on the user's screen under the heading
     */
    public function description(): ?string
    {
        return 'Детальная информация о заявке и история обработки';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Link::make('Назад к списку')
                ->icon('arrow-left')
                ->route('platform.financial-assistance.requests'),

            Link::make('HTML превью')
                ->icon('monitor')
                ->route('platform.financial-assistance.request.html', $this->request->id)
                ->target('_blank'),

//            ModalToggle::make('Обработать заявку')
//                ->icon('pencil')
//                ->modal('processRequestModal')
//                ->method('processRequest')
//                ->canSee($this->request->status == 1),

            Button::make('Одобрить')
                ->icon('check')
                ->method('approveRequest')
                ->confirm('Вы уверены, что хотите одобрить эту заявку?')
                ->class('btn btn-success')
                ->canSee($this->request->status == 1),

            Button::make('Отклонить')
                ->icon('close')
                ->method('rejectRequest')
                ->confirm('Вы уверены, что хотите отклонить эту заявку?')
                ->class('btn btn-danger')
                ->canSee($this->request->status == 1),
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
            // Основная информация о заявке
            Layout::view('orchid.financial-assistance-request-info', [
                'request' => $this->request,
            ]),

            // Детали заявления
            Layout::view('orchid.financial-assistance-request-details', [
                'request' => $this->request,
            ]),

            // История статусов
            Layout::view('orchid.financial-assistance-request-history', [
                'request' => $this->request,
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
                ]),
            ])
            ->title('Обработка заявки #' . $this->request->id)
            ->applyButton('Сохранить решение'),
        ];
    }

    /**
     * Обработка заявки
     */
    public function processRequest(Request $request)
    {
        $status = $request->get('status');
        $signerId = $request->get('signer_id');
        $comment = $request->get('comment');

        $this->request->update([
            'status' => $status,
            'id_signer' => $signerId,
            'comment' => $comment,
            'processed_at' => now(),
        ]);

        // Создаем запись в истории статусов
        $this->request->statusHistory()->create([
            'new_status' => $status,
            'id_user' => auth()->id(),
            'comment' => $comment,
        ]);

        $statusName = $status == 2 ? 'одобрена' : 'отклонена';
        Alert::info("Заявка #{$this->request->id} была {$statusName}.");

        return redirect()->route('platform.financial-assistance.request.view', $this->request->id);
    }

    /**
     * Быстрое одобрение заявки
     */
    public function approveRequest()
    {
        $this->request->update([
            'status' => 2,
            'processed_at' => now(),
        ]);

        $this->request->statusHistory()->create([
            'new_status' => 2,
            'id_user' => auth()->id(),
            'comment' => 'Быстрое одобрение',
        ]);

        Alert::info("Заявка #{$this->request->id} одобрена.");

        return redirect()->route('platform.financial-assistance.request.view', $this->request->id);
    }

    /**
     * Быстрое отклонение заявки
     */
    public function rejectRequest()
    {
        $this->request->update([
            'status' => 3,
            'processed_at' => now(),
        ]);

        $this->request->statusHistory()->create([
            'new_status' => 3,
            'id_user' => auth()->id(),
            'comment' => 'Быстрое отклонение',
        ]);

        Alert::info("Заявка #{$this->request->id} отклонена.");

        return redirect()->route('platform.financial-assistance.request.view', $this->request->id);
    }
}
