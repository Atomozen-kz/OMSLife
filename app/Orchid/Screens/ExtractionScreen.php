<?php

namespace App\Orchid\Screens;

use App\Models\ExtractionCompany;
use App\Models\ExtractionIndicator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Modal;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class ExtractionScreen extends Screen
{
    public function query(Request $request): iterable
    {

        $indicators = DB::table('extraction_indicators')
            ->join('extraction_companies', 'extraction_indicators.company_id', '=', 'extraction_companies.id')
            ->select(
                'extraction_indicators.date',
                'extraction_companies.name_ru as company',
                'extraction_indicators.plan',
                'extraction_indicators.real'
            )
            ->get()
            ->groupBy('date');
//            dd($indicators);
        return [
            'indicators' => ExtractionIndicator::with('company')->paginate(),
            'indicators_new' => $indicators->map(function ($group) {
                return $group->map(function ($item) {
                    return [
                        'date' => Carbon::make($item->date)->format('d.m.Y'),
                        'company' => $item->company,
                        'plan' => $item->plan,
                        'real' => $item->real,
                    ];
                });
            }),
            'indicators_1' => ExtractionIndicator::with('company')->where('company_id', 1)->orderByDesc('date')->paginate(),
            'indicators_2' => ExtractionIndicator::with('company')->where('company_id', 2)->orderByDesc('date')->paginate(),
            'indicators_3' => ExtractionIndicator::with('company')->where('company_id', 3)->orderByDesc('date')->paginate(),
            'indicators_4' => ExtractionIndicator::with('company')->where('company_id', 4)->orderByDesc('date')->paginate(),
            'indicators_5' => ExtractionIndicator::with('company')->where('company_id', 5)->orderByDesc('date')->paginate(),
            'companies' => ExtractionCompany::paginate(),
        ];
    }

    public function name(): ?string
    {
        return 'Добыча нефти';
    }

    public function commandBar(): iterable
    {
        return [
            ModalToggle::make('Список компаний')
                ->modal('companiesModal')
                ->modalTitle('Список добывающих компаний')
                ->icon('list'),

            ModalToggle::make('Добавить показатель')
                ->modal('addMultipleIndicatorsModal')
                ->method('saveMultipleIndicators')
                ->icon('plus'),
        ];
    }

    public function layout(): iterable
    {
        return [
            // Таблица показателей добычи
            Layout::tabs([
                'НГДУ-1' => $this->getExtractionTable('indicators_1'),
                'НГДУ-2' => $this->getExtractionTable('indicators_2'),
                'НГДУ-3' => $this->getExtractionTable('indicators_3'),
                'НГДУ-4' => $this->getExtractionTable('indicators_4'),
                'Западный тенге' => $this->getExtractionTable('indicators_5'),
            ]),

//            Layout::table('indicators_new', [
//                TD::make('date', 'Дата')->render(function ($indicatorsByDate, $date) {
////                    dd($indicatorsByDate);
//                    return 'ss';
//                }),
//                TD::make('ngdu1', 'НГДУ-1')->render(function ($indicatorsByDate) {
//                    return isset($indicatorsByDate['НГДУ-1'])
//                        ? "План: {$indicatorsByDate['НГДУ-1']['plan']}<br>Факт: {$indicatorsByDate['НГДУ-1']['real']}"
//                        : '-';
//                }),
//                TD::make('ngdu2', 'НГДУ-2')->render(function ($indicatorsByDate) {
//                    return isset($indicatorsByDate['НГДУ-2'])
//                        ? "План: {$indicatorsByDate['НГДУ-2']['plan']}<br>Факт: {$indicatorsByDate['НГДУ-2']['real']}"
//                        : '-';
//                }),
//                TD::make('ngdu3', 'НГДУ-3')->render(function ($indicatorsByDate) {
//                    return isset($indicatorsByDate['НГДУ-3'])
//                        ? "План: {$indicatorsByDate['НГДУ-3']['plan']}<br>Факт: {$indicatorsByDate['НГДУ-3']['real']}"
//                        : '-';
//                }),
//                TD::make('ngdu4', 'НГДУ-4')->render(function ($indicatorsByDate) {
//                    return isset($indicatorsByDate['НГДУ-4'])
//                        ? "План: {$indicatorsByDate['НГДУ-4']['plan']}<br>Факт: {$indicatorsByDate['НГДУ-4']['real']}"
//                        : '-';
//                }),
//            ]),

//            Layout::table('indicators', [
//                TD::make('date', 'Дата')->render(function (ExtractionIndicator $indicator) {
//                    return Carbon::make($indicator->date)->format('d.m.Y');
//                }),
//                TD::make('text','')->render(function () {
//                    return 'План:<br>Факт:';
//                }),
//                TD::make('ngdu-1', 'НГДУ-1')->render(
//                    function (ExtractionIndicator $indicator) {
//                        return $indicator->plan. '<br>' . $indicator->real;
//                    }
//                ),
//                TD::make('ngdu-2', 'НГДУ-2')->render(
//                    function (ExtractionIndicator $indicator) {
//                        return $indicator->plan. '<br>' . $indicator->real;
//                    }
//                ),
//                TD::make('ngdu-3', 'НГДУ-3')->render(
//                    function (ExtractionIndicator $indicator) {
//                        return $indicator->plan. '<br>' . $indicator->real;
//                    }
//                ),
//                TD::make('ngdu-4', 'НГДУ-4')->render(
//                    function (ExtractionIndicator $indicator) {
//                        return $indicator->plan. '<br>' . $indicator->real;
//                    }
//                ),
//                TD::make('z_tenge', 'Западный тенге')->render(
//                    function (ExtractionIndicator $indicator) {
//                        return $indicator->plan. '<br>' . $indicator->real;
//                    }
//                ),
//            ]),

            // Модальное окно: список компаний
            Layout::modal('companiesModal', [
                Layout::table('companies', [
                    TD::make('name_ru', 'Название (RU)'),
                    TD::make('name_kz', 'Название (KZ)'),
                    TD::make('name_code', 'Код'),
                    TD::make('Действия')->render(function (ExtractionCompany $company) {
                        return ModalToggle::make('Редактировать')
                            ->modal('companyModal')
                            ->method('saveCompany')
                            ->asyncParameters(['company' => $company->id])
                            ->icon('pencil');
                    }),
                ]),
                Layout::rows([
                    ModalToggle::make('Добавить компанию')
                        ->modal('companyModal')
                        ->method('saveCompany')
                        ->icon('plus'),
                ]),
            ])->title('Список компаний'),

            // Модальное окно: добавление/редактирование компании
            Layout::modal('companyModal', [
                Layout::rows([
                    Input::make('company.id')->type('hidden'),
                    Input::make('company.name_ru')->title('Название (RU)')->required(),
                    Input::make('company.name_kz')->title('Название (KZ)')->required(),
                    Input::make('company.name_code')->title('Код')->required(),
                ]),
            ])->title('Добавить/Редактировать компанию')
                ->async('asyncExtractionCompany')
                ->applyButton('Сохранить')
                ->closeButton('Отмена'),

            Layout::modal('addMultipleIndicatorsModal', [
                Layout::rows([
                    // Поле для выбора даты
                    Input::make('indicators.date')
                        ->title('Дата')
                        ->type('date')
                        ->required(),

                    // Динамическая генерация полей для каждой компании
                    ...ExtractionCompany::all()->flatMap(function ($company) {
                        return [
                            Input::make("indicators.companies.{$company->id}.name")
                                ->value($company->name_ru)
                                ->title('Компания')
                                ->disabled(),
                            Group::make([
                                Input::make("indicators.companies.{$company->id}.plan")
                                    ->title('План')
                                    ->type('number')
                                    ->type('tel')
                                    ->required(),

                                Input::make("indicators.companies.{$company->id}.real")
                                    ->title('Фактическая добыча')
                                    ->type('number')
                                    ->type('tel')
                                    ->required()
                            ])

                        ];
                    })->toArray(),
                ]),
            ])->title('Добавить показатели для всех компаний')
                ->method('saveMultipleIndicators')
                ->applyButton('Сохранить')
                ->closeButton('Отмена'),


            // Модальное окно: добавление/редактирование показателя
            Layout::modal('indicatorModal', [
                Layout::rows([
                    Input::make('indicator.id')->type('hidden'),
                    Relation::make('indicator.company_id')
                        ->value(1)
                        ->fromModel(ExtractionCompany::class, 'name_ru', 'id')
                        ->title('Компания')
                        ->required(),
                    Group::make([
                        Input::make('indicator.plan')->title('План')->type('number')->required(),
                        Input::make('indicator.real')->title('Фактическая добыча')->type('number')->required(),
                    ]),
                    Input::make('indicator.date')
                        ->readonly()
                        ->title('Дата')
                        ->required(),
                ]),
            ])->title('Редактировать показатель')
                ->async('asyncExtractionIndicator')
                ->applyButton('Сохранить')
                ->closeButton('Отмена'),
        ];
    }

    public function getExtractionTable($target)
    {
        return Layout::table($target, [
                    TD::make('company.name_ru', 'Компания'),

                    TD::make('plan', 'План')->render(fn($indicator) => number_format($indicator->plan,0, ',', ' ')),

                    TD::make('real', 'Фактическая добыча')->render(fn($indicator) => number_format($indicator->real, 0, ', ', ' ')),

                    TD::make('date', 'Дата')->render(fn($indicator) => Carbon::make($indicator->date)->format('d.m.Y')),

                    TD::make('Действия')->render(function (ExtractionIndicator $indicator) {
                        return ModalToggle::make('Редактировать')
                            ->modal('indicatorModal')
                            ->method('saveIndicator')
                            ->asyncParameters(['indicator' => $indicator->id])
                            ->icon('pencil');
                    }),
                ]);
    }

    public function asyncExtractionCompany(ExtractionCompany $company){
        return [
            'company' => $company
        ];
    }

    public function asyncExtractionIndicator(ExtractionIndicator $indicator){
        return [
            'indicator' => $indicator
        ];
    }

    public function saveCompany(Request $request)
    {
        $data = $request->validate([
            'company.name_ru' => 'required|string|max:255',
            'company.name_kz' => 'required|string|max:255',
            'company.name_code' => 'required|string|max:255',
        ]);

        ExtractionCompany::updateOrCreate(
            ['id' => $request->input('company.id')],
            $data['company']
        );

        Toast::info('Компания успешно сохранена.');
    }

    public function saveIndicator(Request $request)
    {
        $data = $request->validate([
            'indicator.company_id' => 'required|exists:extraction_companies,id',
            'indicator.plan' => 'required|numeric',
            'indicator.real' => 'required|numeric',
            'indicator.date' => 'required|date',
        ]);

        ExtractionIndicator::updateOrCreate(
            ['id' => $request->input('indicator.id')],
            $data['indicator']
        );

        Toast::info('Показатель успешно сохранен.');
    }

    public function saveMultipleIndicators(Request $request)
    {
        $data = $request->validate([
            'indicators.date' => 'required|date',
            'indicators.companies' => 'required|array',
            'indicators.companies.*.plan' => 'required|numeric',
            'indicators.companies.*.real' => 'required|numeric',
            'indicators.companies.*.plan' => 'required|integer|max:2147483646',
            'indicators.companies.*.real' => 'required|integer|max:2147483646',
        ]);

        $date = $data['indicators']['date'];
        $companies = $data['indicators']['companies'];

        foreach ($companies as $companyId => $values) {
            ExtractionIndicator::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'date' => $date,
                ],
                [
                    'plan' => $values['plan'],
                    'real' => $values['real'],
                ]
            );
        }

        Toast::info('Показатели успешно добавлены.');
    }
}
