<?php

namespace App\Orchid\Screens;

use App\Models\OrganizationStructure;
use App\Models\PayrollRequest;
use App\Models\PayrollSlip;
use App\Models\PayrollSlip_404;
use Carbon\Carbon;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class PayrollSlipScreen extends Screen
{
    /**
     * The screen's name.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Расчетные листы';
    }

    /**
     * Query data.
     *
     * @return array
     */
    public function query(): iterable
    {
//        $organizationsMetric = DB::table('payroll_slips')
//            ->join('sotrudniki', 'payroll_slips.sotrudniki_id', '=', 'sotrudniki.id')
//            ->join('organization_structure', 'sotrudniki.organization_id', '=', 'organization_structure.id')
//            ->select('organization_structure.name_ru as organization_name', DB::raw('count(payroll_slips.id) as total'))
//            ->whereNull('organization_structure.parent_id')
//            ->groupBy('organization_structure.id', 'organization_structure.name_ru')
//            ->pluck('total', 'organization_name')
//            ->toArray();
//
//        $chartData = [[
//            'labels'   => array_keys($organizationsMetric),
//            'name'  => 'Количество расчетных листов',
//            'values' => array_values($organizationsMetric),
//        ]];

        return [
            'successful'    => PayrollSlip::orderByDesc('id')->paginate(),
            'not_found'     => PayrollSlip_404::orderByDesc('id')->paginate(),
//            'organizationsData' => $chartData,
        'payroll_requests' => PayrollRequest::with('organization')->orderByDesc('created_at')->paginate()
        ];
    }

    /**
     * Button commands.
     *
     * @return array
     */
    public function commandBar(): array
    {
        return [];
    }

    /**
     * Views.
     *
     * @return array
     */
    public function layout(): array
    {
        return [
//            Layout::chart('organizationsData','sdfsdfsdfsdf')
//            ->type('pie'),
            Layout::tabs([
                'Запросы' => Layout::table('payroll_requests', [
                    TD::make('organization.name_ru', 'Организация'),
                    TD::make('find_count', 'Найдено'),
                    TD::make('not_find_count', 'Не найдено'),
                    TD::make('created_at', 'Начало запроса'),
                    TD::make('updated_at', 'Конец запроса'),
                ]),
                'Успешные записи' => Layout::table('successful', [
                    TD::make('last_name', 'Фамилия'),
                    TD::make('first_name', 'Имя'),
                    TD::make('father_name', 'Отчество'),
                    TD::make('tabel_nomer', 'Табельный номер'),
                    TD::make('month', 'Месяц'),
                    TD::make('date', 'Дата получения')->render(function (PayrollSlip $slip) {
                        return Carbon::make($slip->created_at)->isoFormat('LLL');
                    }),
//                    TD::make('pdf_path', 'PDF')
//                        ->render(function (PayrollSlip $slip) {
//                            return Link::make('Скачать PDF')
//                                ->href(Storage::temporaryUrl($slip->pdf_path, now()->addMinutes(10)))
//                                ->target('_blank');
//                        }),
                ]),
                'Не найдено сотрудник' => Layout::table('not_found', [
                    TD::make('psp_name', 'ПСП'),
                    TD::make('last_name', 'Фамилия'),
                    TD::make('first_name', 'Имя'),
                    TD::make('tabel_nomer', 'Табельный номер'),
                    TD::make('iin', 'ИИН'),
                    TD::make('month', 'Месяц'),
                    TD::make('date', 'Дата получения')->render(function (PayrollSlip_404 $slip) {
                        return Carbon::make($slip->created_at)->isoFormat('LLL');
                    }),
//                    TD::make('pdf', 'PDF')
//                        ->render(function (PayrollSlip_404 $slip) {
//                            return Link::make('Скачать PDF')
//                                ->href(Storage::temporaryUrl($slip->pdf, now()->addMinutes(10)))
//                                ->target('_blank');
//                        }),
                ]),
            ]),
        ];
    }
}
