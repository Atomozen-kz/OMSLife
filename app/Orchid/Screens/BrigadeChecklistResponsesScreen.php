<?php

namespace App\Orchid\Screens;

use App\Exports\BrigadeChecklistResponsesExport;
use App\Models\BrigadeChecklistSession;
use App\Models\BrigadeMaster;
use App\Models\RemontBrigade;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\DateRange;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class BrigadeChecklistResponsesScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(Request $request): iterable
    {
        $query = BrigadeChecklistSession::with(['master.sotrudnik', 'brigade', 'responses.checklistItem'])
            ->orderBy('completed_at', 'desc');

        // Применяем фильтры
        if ($request->filled('brigade_id')) {
            $query->where('brigade_id', $request->input('brigade_id'));
        }

        if ($request->filled('master_id')) {
            $query->where('master_id', $request->input('master_id'));
        }

        if ($request->filled('well_number')) {
            $query->where('well_number', 'like', '%' . $request->input('well_number') . '%');
        }

        if ($request->filled('completed_at')) {
            $dates = $request->input('completed_at');
            if (isset($dates['start']) && $dates['start']) {
                $query->where('completed_at', '>=', $dates['start']);
            }
            if (isset($dates['end']) && $dates['end']) {
                $query->where('completed_at', '<=', $dates['end']);
            }
        }

        return [
            'sessions' => $query->paginate(30),
            'filters' => [
                'brigade_id' => $request->input('brigade_id'),
                'master_id' => $request->input('master_id'),
                'well_number' => $request->input('well_number'),
                'completed_at' => $request->input('completed_at'),
            ],
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'История ответов на чек-листы';
    }

    /**
     * The screen's description.
     *
     * @return string|null
     */
    public function description(): ?string
    {
        return 'Просмотр и экспорт ответов мастеров на чек-листы';
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
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Button::make('Экспорт в Excel')
                ->method('exportToExcel')
                ->icon('cloud-download')
                ->class('btn btn-success'),
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
            Layout::rows([
                Select::make('brigade_id')
                    ->title('Бригада')
                    ->empty('Все бригады')
                    ->fromModel(RemontBrigade::brigades(), 'name', 'id')
                    ->value($this->query(request())['filters']['brigade_id'] ?? null),

                Select::make('master_id')
                    ->title('Мастер')
                    ->empty('Все мастера')
                    ->options(
                        BrigadeMaster::with('sotrudnik')
                            ->get()
                            ->pluck('sotrudnik.full_name', 'id')
                            ->toArray()
                    )
                    ->value($this->query(request())['filters']['master_id'] ?? null),

                Input::make('well_number')
                    ->title('Номер скважины')
                    ->placeholder('Введите номер скважины')
                    ->value($this->query(request())['filters']['well_number'] ?? null),


                DateRange::make('completed_at')
                    ->title('Период заполнения')
                    ->value($this->query(request())['filters']['completed_at'] ?? null),

                Button::make('Применить фильтры')
                    ->method('applyFilters')
                    ->icon('filter')
                    ->class('btn btn-primary'),

                Button::make('Сбросить фильтры')
                    ->method('resetFilters')
                    ->icon('refresh')
                    ->class('btn btn-secondary'),
            ])->title('Фильтры'),

            Layout::table('sessions', [
                TD::make('completed_at', 'Дата и время')
                    ->render(function (BrigadeChecklistSession $session) {
                        return $session->formatted_completed_at;
                    })
                    ->sort(),

                TD::make('full_name_master', 'Мастер (ФИО)')
                    ->render(function (BrigadeChecklistSession $session) {
                        return e($session->full_name_master);
                    }),

                TD::make('brigade_name', 'Бригада')
                    ->render(function (BrigadeChecklistSession $session) {
                        return e($session->brigade_name);
                    }),

                TD::make('well_number', 'Скважина')
                    ->render(function (BrigadeChecklistSession $session) {
                        return e($session->well_number);
                    }),

                TD::make('tk', 'ТК')
                    ->render(function (BrigadeChecklistSession $session) {
                        return e($session->tk);
                    }),

                TD::make('stats', 'Статистика ответов')
                    ->render(function (BrigadeChecklistSession $session) {
                        $dangerous = $session->dangerous_count;
                        $safe = $session->safe_count;
                        $other = $session->other_count;

                        return "
                            <span class='badge bg-danger'>Опасно: {$dangerous}</span>
                            <span class='badge bg-success'>Безопасно: {$safe}</span>
                            <span class='badge bg-info'>Другое: {$other}</span>
                        ";
                    }),

                TD::make('actions', 'Действия')
                    ->render(function (BrigadeChecklistSession $session) {
                        return Link::make('Детали')
                            ->icon('eye')
                            ->route('platform.brigade-checklist.session.detail', ['id' => $session->id])
                            ->class('btn btn-sm btn-primary');
                    })
                    ->canSee(true),
            ]),
        ];
    }

    /**
     * Применить фильтры
     */
    public function applyFilters(Request $request)
    {
        // Фильтры применяются автоматически через query()
    }

    /**
     * Сбросить фильтры
     */
    public function resetFilters()
    {
        return redirect()->route('platform.brigade-checklist.responses');
    }

    /**
     * Экспорт в Excel
     */
    public function exportToExcel(Request $request)
    {
        $query = BrigadeChecklistSession::with(['master.sotrudnik', 'brigade', 'responses.checklistItem'])
            ->orderBy('completed_at', 'desc');

        // Применяем те же фильтры, что и в query()
        if ($request->filled('brigade_id')) {
            $query->where('brigade_id', $request->input('brigade_id'));
        }

        if ($request->filled('master_id')) {
            $query->where('master_id', $request->input('master_id'));
        }

        if ($request->filled('well_number')) {
            $query->where('well_number', 'like', '%' . $request->input('well_number') . '%');
        }

        if ($request->filled('completed_at')) {
            $dates = $request->input('completed_at');
            if (isset($dates['start']) && $dates['start']) {
                $query->where('completed_at', '>=', $dates['start']);
            }
            if (isset($dates['end']) && $dates['end']) {
                $query->where('completed_at', '<=', $dates['end']);
            }
        }

        $filename = 'brigade_checklist_responses_' . date('Y-m-d_His') . '.xlsx';

        return Excel::download(
            new BrigadeChecklistResponsesExport($query->get()),
            $filename
        );
    }
}
