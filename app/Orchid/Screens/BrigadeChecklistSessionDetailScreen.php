<?php

namespace App\Orchid\Screens;

use App\Models\BrigadeChecklistSession;
use Barryvdh\DomPDF\Facade\Pdf;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Screen\Sight;
use Orchid\Support\Facades\Layout;

class BrigadeChecklistSessionDetailScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     */
    public function query($id): iterable
    {
        $session = BrigadeChecklistSession::findOrFail($id);
        $session->load(['master.sotrudnik', 'brigade', 'responses.checklistItem']);

        return [
            'session' => $session,
            'responses' => $session->responses,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'Детали чек-листа';
    }

    /**
     * The screen's description.
     */
    public function description(): ?string
    {
        return 'Подробная информация о заполненном чек-листе';
    }

    /**
     * The permissions required to access this screen.
     */
    public function permission(): ?iterable
    {
        return ['platform.brigade-checklist'];
    }

    /**
     * The screen's action buttons.
     */
    public function commandBar(): iterable
    {
        return [
            Button::make('Экспорт в PDF')
                ->method('exportToPdf')
                ->icon('printer')
                ->class('btn btn-danger'),

            Button::make('Назад к списку')
                ->icon('arrow-left')
                ->route('platform.brigade-checklist.responses')
                ->class('btn btn-secondary'),
        ];
    }

    /**
     * The screen's layout elements.
     */
    public function layout(): iterable
    {
        return [
            Layout::legend('session', [
                Sight::make('completed_at', 'Дата и время заполнения')
                    ->render(fn($session) => $session->formatted_completed_at),

                Sight::make('full_name_master', 'Мастер (ФИО)'),

                Sight::make('brigade_name', 'Бригада'),

                Sight::make('well_number', 'Номер скважины'),

                Sight::make('tk', 'ТК'),

                Sight::make('stats', 'Статистика ответов')
                    ->render(function ($session) {
                        return "
                            <span class='badge bg-danger'>Опасно: {$session->dangerous_count}</span>
                            <span class='badge bg-success'>Безопасно: {$session->safe_count}</span>
                            <span class='badge bg-info'>Другое: {$session->other_count}</span>
                        ";
                    }),
            ])->title('Общая информация'),

            Layout::view('orchid.brigade-checklist.responses-table'),
        ];
    }

    /**
     * Экспорт в PDF
     */
    public function exportToPdf($id)
    {
        $session = BrigadeChecklistSession::findOrFail($id);
        $session->load(['master.sotrudnik', 'brigade', 'responses.checklistItem']);

        $pdf = Pdf::loadView('pdf.brigade-checklist-session', [
            'session' => $session,
        ]);

        $filename = 'checklist_session_' . $session->id . '_' . date('Y-m-d_His') . '.pdf';

        return $pdf->download($filename);
    }
}
