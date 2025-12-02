<?php

namespace App\Orchid\Screens;

use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Label;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Sight;
use App\Models\PayrollSlip;
use App\Models\Sotrudniki;
use App\Models\OrganizationStructure;
use Illuminate\Support\Facades\DB;

class StatisticsMonthDetailScreen extends Screen
{
    public $month;

    public function query($month): iterable
    {
        $this->month = $month;

        // Общее число сотрудников
        $totalEmployees = Sotrudniki::count();

        // Количество уникальных сотрудников, получивших жировку в этом месяце
        $received = PayrollSlip::where('month', $month)
            ->whereNotNull('sotrudniki_id')
            ->distinct('sotrudniki_id')
            ->count('sotrudniki_id');

        $notReceived = $totalEmployees - $received;
        if ($notReceived < 0) {
            $notReceived = 0;
        }

        // Получаем список сотрудников, которые НЕ получили жировку в этом месяце
        // Сначала получим список sotrudniki_id, которые получили
        $receivedIds = PayrollSlip::where('month', $month)
            ->whereNotNull('sotrudniki_id')
            ->distinct()
            ->pluck('sotrudniki_id')
            ->toArray();

        // --- Оптимизация: предварительный подсчёт сотрудников по organization_id ---
        $sCounts = Sotrudniki::whereNotNull('organization_id')
            ->select('organization_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('organization_id')
            ->pluck('cnt', 'organization_id')
            ->toArray();

        $receivedCounts = [];
        if (!empty($receivedIds)) {
            $receivedCounts = Sotrudniki::whereIn('id', $receivedIds)
                ->whereNotNull('organization_id')
                ->select('organization_id', DB::raw('COUNT(*) as cnt'))
                ->groupBy('organization_id')
                ->pluck('cnt', 'organization_id')
                ->toArray();
        }

        // Статистика по корневым организациям (ПСП) — теперь без count() внутри цикла
        $rootStats = [];
        $roots = OrganizationStructure::whereNull('parent_id')->get();
        foreach ($roots as $root) {
            $orgIds = $root->allRelatedOrganizationIds();

            $totalUnder = 0;
            $receivedUnder = 0;
            foreach ($orgIds as $oid) {
                $totalUnder += isset($sCounts[$oid]) ? (int)$sCounts[$oid] : 0;
                $receivedUnder += isset($receivedCounts[$oid]) ? (int)$receivedCounts[$oid] : 0;
            }

            $notReceivedUnder = $totalUnder - $receivedUnder;
            if ($notReceivedUnder < 0) {
                $notReceivedUnder = 0;
            }

            $rootStats[] = (object) [
                'id' => $root->id,
                'name' => $root->name_ru ?? $root->name_kz ?? '—',
                'received' => $receivedUnder,
                'not_received' => $notReceivedUnder,
                'total' => $totalUnder,
            ];
        }

        // Статистика для сотрудников без organization_id
        $orphanTotal = isset($sCounts[null]) ? (int)$sCounts[null] : Sotrudniki::whereNull('organization_id')->count();
        // Но в sCounts мы не храним NULL ключ — получим явно
        $orphanTotal = Sotrudniki::whereNull('organization_id')->count();
        $orphanReceived = Sotrudniki::whereNull('organization_id')
            ->whereIn('id', $receivedIds)
            ->count();
        $orphanNotReceived = $orphanTotal - $orphanReceived;
        if ($orphanNotReceived < 0) {
            $orphanNotReceived = 0;
        }

        if ($orphanTotal > 0) {
            $rootStats[] = (object) [
                'id' => 0,
                'name' => 'Без подразделения',
                'received' => $orphanReceived,
                'not_received' => $orphanNotReceived,
                'total' => $orphanTotal,
            ];
        }

        // --- Оптимизация N+1 для OrganizationStructure: загрузим всех используемых организаций и их родителей ---
        $notReceivedEmployees = Sotrudniki::whereNotIn('id', $receivedIds)
            ->with('organization')
            ->get();

        // Собираем все organization_id, которые есть у сотрудников
        $orgIds = $notReceivedEmployees->pluck('organization_id')->filter()->unique()->values()->all();

        // Загрузим рекурсивно все организации и их родителей только один раз
        $loadedOrgs = [];
        $pending = $orgIds;
        while (!empty($pending)) {
            $batch = OrganizationStructure::whereIn('id', $pending)->get();
            $newPending = [];
            foreach ($batch as $o) {
                if (!isset($loadedOrgs[$o->id])) {
                    $loadedOrgs[$o->id] = $o;
                    if ($o->parent_id) {
                        $newPending[] = $o->parent_id;
                    }
                }
            }
            // Оставляем только те parent_id, которых ещё нет в loadedOrgs
            $pending = array_values(array_filter(array_unique($newPending), function ($id) use ($loadedOrgs) {
                return !isset($loadedOrgs[$id]);
            }));
        }

        // Установим relation 'parent' между загруженными организациями в памяти
        foreach ($loadedOrgs as $oid => $orgModel) {
            if ($orgModel->parent_id && isset($loadedOrgs[$orgModel->parent_id])) {
                $orgModel->setRelation('parent', $loadedOrgs[$orgModel->parent_id]);
            }
        }

        // Заменим relation 'organization' у сотрудников на предзагруженные модели (с parent связями)
        foreach ($notReceivedEmployees as $employee) {
            if ($employee->organization_id && isset($loadedOrgs[$employee->organization_id])) {
                $employee->setRelation('organization', $loadedOrgs[$employee->organization_id]);
            }
        }

        // Сортируем по корневому подразделению (теперь без дополнительных запросов)
        $notReceivedEmployees = $notReceivedEmployees->sortBy(function ($item) {
            $org = $item->organization;
            if (!$org) {
                return '';
            }
            $firstParent = $org->first_parent ?? null;
            $root = $firstParent ? $firstParent : $org;
            return $root->name_ru ?? '';
        })->values()
          ->map(function ($item, $index) {
                $item->ordinal = $index + 1;
                return $item;
            });

        return [
            'month' => $month,
            'totalEmployees' => $totalEmployees,
            'received' => $received,
            'notReceived' => $notReceived,
            'rootStats' => $rootStats,
            'notReceivedEmployees' => $notReceivedEmployees,
        ];
    }

    public function name(): ?string
    {
        return 'Статистика: Просмотр месяца';
    }

    public function commandBar(): iterable
    {
        return [
            Link::make('Назад')->route('platform.statistics'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Label::make('month')->title('Месяц'),
                Label::make('totalEmployees')->title('Всего сотрудников'),
                Label::make('received')->title('Получили жировки'),
                Label::make('notReceived')->title('Не получили жировок'),
            ]),

            // Статистика по корневым подразделениям (ПСП)
            Layout::table('rootStats', [
                TD::make('name', 'ПСП')->render(function ($item) { return $item->name; }),
                TD::make('received', 'Получили')->render(function ($item) {
                    $val = number_format($item->received);
                    if ((int)$item->received > 0) {
                        return "<span class='text-success'>{$val}</span>";
                    }
                    return "<span class='text-muted'>{$val}</span>";
                }),
                TD::make('not_received', 'Не получили')->render(function ($item) {
                    $val = number_format($item->not_received);
                    if ((int)$item->not_received > 0) {
                        return "<span class='text-danger'>{$val}</span>";
                    }
                    return "<span class='text-muted'>{$val}</span>";
                }),
                TD::make('total', 'Всего')->render(function ($item) { return number_format($item->total); }),
            ]),

            Layout::table('notReceivedEmployees', [
                TD::make('ordinal', '#')->render(function ($item) {
                    return $item->ordinal ?? '';
                })->width('70px'),
                TD::make('root_org', 'ПСП')->render(function ($item) {
                    $org = $item->organization;
                    if (!$org) {
                        return '';
                    }
                    // Корневая организация (для информации)
                    $firstParent = $org->first_parent ?? null;
                    $root = $firstParent ? $firstParent : $org;
                    return $root->name_ru ?? '';
                })->width('150px'),
                TD::make('org', 'Организация')->render(function ($item) {
                    $org = $item->organization;
                    return $org->name_ru ?? '';
                })->width('450px'),
                TD::make('tabel_nomer', 'Табельный номер')->render(function ($item) {
                    return $item->tabel_nomer;
                }),
                TD::make('fio', 'ФИО')->render(function ($item) {
                    return $item->full_name;
                }),

                // телефон скрыт по требованию
            ]),
        ];
    }
}
