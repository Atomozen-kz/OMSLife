<?php

namespace App\Orchid\Screens;

use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Label;
use Orchid\Screen\TD;
use Orchid\Screen\Actions\Link;
use App\Models\PayrollSlip;
use App\Models\Sotrudniki;
use App\Models\OrganizationStructure;
use Illuminate\Support\Facades\DB;
use App\Models\TrainingType;
use Carbon\Carbon;

class StatisticsScreen extends Screen
{
    public function query(): iterable
    {
        // Сначала определим общее число сотрудников, которым положены расчетные листы.
        // Предпочтение отдаем флагу is_payroll_slip_func, если таких записей нет — берём всех сотрудников.

        $totalEmployees = Sotrudniki::count();

        // Группируем расчетные листы по текстовому полю `month` и считаем уникальных сотрудников (один сотрудник за месяц считается один раз)
        $stats = PayrollSlip::select(
                'month',
                DB::raw('COUNT(DISTINCT sotrudniki_id) as total'),
                DB::raw('MAX(created_at) as last_created_at')
            )
            ->whereNotNull('sotrudniki_id')
            ->groupBy('month')
            ->orderByRaw('MAX(created_at) DESC')
            ->get()
            ->map(function ($item) use ($totalEmployees) {
                // количество сотрудников, не получивших жировок в этом месяце
                $received = (int) $item->total;
                $notReceived = $totalEmployees - $received;
                if ($notReceived < 0) {
                    $notReceived = 0;
                }
                $item->not_received = $notReceived;
                return $item;
            });

        // Новая логика: для каждой корневой структуры (parent_id IS NULL) подсчитаем сотрудников
        // и сколько из них имеют/не имеют запись в `sotrudniki_codes` (по sotrudnik_id)

        $structureStats = [];
        $roots = OrganizationStructure::whereNull('parent_id')->get();
        foreach ($roots as $root) {
            $structureIds = $this->getDescendantIds($root->id);

            // Получаем id сотрудников в этих структурах
            $employeeIds = Sotrudniki::whereIn('organization_id', $structureIds)->pluck('id')->toArray();

            $total = count($employeeIds);

            $withCode = 0;
            if (!empty($employeeIds)) {
                $withCode = DB::table('sotrudniki_codes')
                    ->whereIn('sotrudnik_id', $employeeIds)
                    ->count();
            }

            $withoutCode = max(0, $total - $withCode);

            $structureStats[] = (object) [
                'id' => $root->id,
                'name_ru' => $root->name_ru,
                'total' => $total,
                'with_code' => $withCode,
                'without_code' => $withoutCode,
            ];
        }

        // --- КУЦ: статистика по типам обучения ---
        $today = Carbon::today();
        $kucStats = [];

        $types = TrainingType::orderBy('name_ru')->get();
        foreach ($types as $type) {
            // subquery: для каждого сотрудника берем максимальную completion_date по этому типу
            $latest = DB::table('training_records as tr')
                ->select('tr.id_sotrudnik', DB::raw('MAX(tr.completion_date) as max_completion'))
                ->where('tr.id_training_type', $type->id)
                ->groupBy('tr.id_sotrudnik');

            // присоединяем полную запись, соответствующую max(completion_date)
            $records = DB::table('training_records as tr')
                ->joinSub($latest, 'latest', function ($join) {
                    $join->on('tr.id_sotrudnik', '=', 'latest.id_sotrudnik')
                        ->on('tr.completion_date', '=', 'latest.max_completion');
                })
                ->where('tr.id_training_type', $type->id)
                ->select('tr.id_sotrudnik', 'tr.validity_date')
                ->get();

            $total = $records->count();
            $notExpired = 0;
            $expired = 0;
            foreach ($records as $r) {
                // если validity_date отсутствует — считаем просроченным
                if (empty($r->validity_date)) {
                    $expired++;
                    continue;
                }
                $validity = Carbon::parse($r->validity_date);
                if ($validity->lt($today)) {
                    $expired++;
                } else {
                    $notExpired++;
                }
            }

            $kucStats[] = (object)[
                'id' => $type->id,
                'name_ru' => $type->name_ru,
                'total' => $total,
                'not_expired' => $notExpired,
                'expired' => $expired,
            ];
        }

        return [
            'payrollStats' => $stats,
            'totalEmployees' => $totalEmployees,
            'structureStats' => collect($structureStats),
            'kucStats' => collect($kucStats),
        ];
    }

    public function name(): ?string
    {
        return 'Статистика';
    }

    public function description(): ?string
    {
        return 'Пустая страница статистики';
    }

    public function commandBar(): iterable
    {
        return [];
    }

    public function layout(): iterable
    {
        return [
            Layout::table('payrollStats', [
                TD::make('month', 'Месяц')->render(function ($item) {
                    return Link::make($item->month)
                        ->route('platform.statistics.month', $item->month);
                }),

                TD::make('total', 'Количество жировок')->render(function ($item) {
                    return number_format($item->total);
                }),

                TD::make('not_received', 'Не получили жировок')->render(function ($item) {
                    return number_format($item->not_received ?? 0);
                }),
            ]),

            // Новая таблица: по корневым структурам сколько сотрудников с/без milk QR
            Layout::table('structureStats', [
                TD::make('name_ru', 'Структура')->render(function ($item) {
                    return $item->name_ru;
                }),

                TD::make('total', 'Всего сотрудников')->render(function ($item) {
                    return number_format($item->total);
                }),

                TD::make('with_code', 'С кодом')->render(function ($item) {
                    return number_format($item->with_code);
                }),

                TD::make('without_code', 'Без кода')->render(function ($item) {
                    return number_format($item->without_code);
                }),
            ]),

            // Новая таблица: КУЦ — по типам обучения (последняя запись по сотруднику)
            Layout::table('kucStats', [
                TD::make('name_ru', 'КУЦ (тип)')->render(function ($item) {
                    return $item->name_ru;
                }),

                TD::make('total', 'Всего записей')->render(function ($item) {
                    return number_format($item->total);
                }),

                TD::make('not_expired', 'Не просрочен')->render(function ($item) {
                    return number_format($item->not_expired);
                }),

                TD::make('expired', 'Просрочен')->render(function ($item) {
                    return number_format($item->expired);
                }),
            ]),

            // Небольшая подсказка снизу
             Layout::rows([
                 Label::make('info')->title('Здесь будет отображаться статистика по расчетным листам. По каждому текстовому значению поля month показано количество записей.'),
             ]),
        ];
    }

    /**
     * Собирает id всех потомков (включая корень) методом BFS
     */
    private function getDescendantIds(int $rootId): array
    {
        $ids = [$rootId];
        $queue = [$rootId];

        while (!empty($queue)) {
            $current = array_shift($queue);
            $children = OrganizationStructure::where('parent_id', $current)->pluck('id')->toArray();
            foreach ($children as $c) {
                if (!in_array($c, $ids, true)) {
                    $ids[] = $c;
                    $queue[] = $c;
                }
            }
        }

        return $ids;
    }
}
