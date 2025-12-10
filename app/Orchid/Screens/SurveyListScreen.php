<?php

namespace App\Orchid\Screens;

use App\Models\OrganizationStructure;
use App\Models\Survey;
use Carbon\Carbon;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;

class SurveyListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
//            'surveys' => Survey::paginate(),
            'surveys_kz' => Survey::where('lang', 'kz')->orderBy('id', 'DESC')->paginate(),
            'surveys_ru' => Survey::where('lang', 'ru')->orderBy('id', 'DESC')->paginate(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Опросы';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            ModalToggle::make('Создать опрос')
                ->modal('createOrUpdateSurveyModal')
                ->method('createOrUpdateSurvey')
                ->icon('plus'),
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
            // Таблица опросов
           Layout::tabs([
               'Казахский' => $this->surveysTable('surveys_kz'),
               'Русский' => $this->surveysTable('surveys_ru'),
           ]),

            // Модальное окно для создания/редактирования опроса
            Layout::modal('createOrUpdateSurveyModal', [
                Layout::rows([
                    Input::make('survey.id')->type('hidden'),

                    Switcher::make('survey.is_anonymous')
                        ->title('Анонимный')
                        ->sendTrueOrFalse(),

                    Switcher::make('survey.status')
                        ->title('Статус')
                        ->sendTrueOrFalse(),

                    Select::make('survey.lang')
                        ->title('Язык опроса')
                        ->options([
                            'ru' => 'Русский',
                            'kz' => 'Қазақша',
                        ])
                        ->required()
                        ->help('Выберите язык опроса'),

//                    Switcher::make('survey.is_all')
//                        ->title('Доступен всем сотрудникам')
//                        ->sendTrueOrFalse()
//                        ->help('Если отключено, выберите организации, для которых доступен опрос')
//                        ->addClass('is-all-switcher'),

                    // Поле для выбора организаций, отображается только если is_all = false
//                    Relation::make('survey.organizations')
//                        ->title('Организации')
//                        ->fromModel(OrganizationStructure::class, 'name_ru') // Или другое поле для отображения
//                        ->multiple()
//                        ->addClass('organizations-field') // Уникальный класс
//                        ->applyScope('FirstParent')
//                        ->help('Выберите организации, для которых доступен опрос'),

                    Input::make('survey.title')
                        ->title('Название опроса')
                        ->required(),
                    TextArea::make('survey.description')
                        ->title('Описание опроса')
                        ->rows(3),

                ]),
            ])->title('Создать / Редактировать опрос')
                ->async('asyncGetSurvey')
                ->applyButton('Сохранить')
                ->closeButton('Отмена'),
            Layout::view('orchid.survey-toggle-script'),
        ];
    }


    public function surveysTable($target){
        return  Layout::table($target, [

            TD::make('status', 'Статус')->render(function (Survey $survey) {
                return $survey->status ? '🟢' : '🔴';
            }),

            TD::make('title', 'Название и описание')->render(function (Survey $survey) {
                return $survey->title.'<br><span style="color:gray">'.$survey->description.'</span>';
            })->width('300px'),

            TD::make('questions', 'Вопросы')->render(function (Survey $survey) {
                return Link::make($survey->questions()->count(). ' вопросов')
                    ->icon('bs.question-circle')
                    ->class('btn btn-sm btn-warning')
                    ->route('platform.survey.question', ['survey' => $survey->id]);
            }),

            TD::make('statistika', 'Ответили')->render(function (Survey $survey) {
                return $survey->responses()->count().' человек'
                    . Link::make('Просмотреть отчет')
                        ->route('platform.survey.report', ['survey' => $survey->id])
                        ->icon('bs.bar-chart-fill');
            })->align('center'),

            TD::make('parameters ', 'Параметры')
                ->render(function (Survey $survey) {
                    $ret = array();
                    $ret[] = $survey->is_all ? 'Все сотрудники' : 'Организации: '.join(', ', $survey->organizations->pluck('name_ru')->toArray);
                    $ret[] = $survey->is_anonymous ? '🥷 Анонимный' : '👷‍♂️ Публичный';
                    $ret[] = Carbon::make($survey->created_at)->isoFormat('LLL');
                    $ret[] = $survey->lang == 'ru' ? 'Русский' : 'Қазақша';

                    return join('<br>', $ret);
                }),

            TD::make('actions', 'Действия')
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(function (Survey $survey) {
                    return

                        ModalToggle::make('Редактировать')
                            ->method('createOrUpdateSurvey')
                            ->modal('createOrUpdateSurveyModal')
                            ->asyncParameters(['survey' => $survey->id])
                            ->icon('pencil')
                        . ' ' .
                        Button::make('Удалить')
                            ->method('deleteSurvey')
                            ->parameters(['survey' => $survey->id])
                            ->icon('trash')
                            ->confirm('Вы уверены, что хотите удалить этот опрос?');
                }),
        ]);
    }

    public function asyncGetSurvey(Survey $survey)
    {
        return [
            'survey' => $survey
        ];
    }

    public function createOrUpdateSurvey(\Illuminate\Http\Request $request)
    {
//        $isAll = $request['survey']['is_all'];
//        if ($request['survey']['organizations'] == null){
//            $isAll = true;
//        }
        $isAll = true;
        $survey = Survey::updateOrCreate(
            ['id' => $request['survey']['id'] ?? null],
            [
                'title' => $request['survey']['title'],
                'is_anonymous' => $request['survey']['is_anonymous'],
                'status' => $request['survey']['status'],
                'description' => $request['survey']['description'],
                'lang' => $request['survey']['lang'],
                'is_all' => $isAll,
            ]
        );
//        // Обновление связи с организациями
//        if (!$survey->is_all) {
//            $survey->organizations()->sync($request->input('survey.organizations', []));
//        } else {
//            $survey->organizations()->detach();
//        }


        Alert::info('Опрос успешно сохранен.');

        return redirect()->route('platform.surveys');
    }

    /**
     * Обработчик удаления опроса.
     *
     * @param Survey $survey
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteSurvey(Survey $survey)
    {
        $survey->delete();

        Alert::info('Опрос успешно удален.');
    }
}
