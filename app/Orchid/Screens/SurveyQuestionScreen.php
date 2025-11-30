<?php

namespace App\Orchid\Screens;

use App\Models\Survey;
use App\Models\SurveyOption;
use App\Models\SurveyQuestion;
use Illuminate\Http\Request;
use Orchid\Screen\Fields\Matrix;
use Orchid\Screen\Screen;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Layouts\Modal;
use Orchid\Screen\Layouts\Rows;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Toast;
use function Termwind\render;

class SurveyQuestionScreen extends Screen
{
    /**
     * @var Survey
     */
    public $survey;

    /**
     * Название экрана.
     *
     * @var string
     */
    public $name = 'Управление Вопросами Опроса';

    /**
     * Описание экрана.
     *
     * @var string
     */
    public $description = 'Добавление, редактирование и удаление вопросов и вариантов ответов для опроса.';

    /**
     * Разрешения.
     *
     * @var array
     */
//    public $permission = [
//        'platform.surveys.manage',
//    ];

    /**
     * Получение данных для экрана.
     *
     * @param Survey $survey
     * @return array
     */
    public function query(Survey $survey): iterable
    {
        $this->survey = $survey;

        return [
            'survey' => $survey,
            'questions' => $survey->questions()->with('answers')->paginate(),
        ];
    }

    /**
     * Установка названия экрана.
     *
     * @return string
     */
    public function name(): string
    {
        return 'Управление Вопросами Опроса: ' . $this->survey->title;
    }

    /**
     * Кнопки для экрана.
     *
     * @return array
     */
    public function commandBar(): iterable
    {
        return [
            ModalToggle::make('Добавить Вопрос')
                ->modal('createOrUpdateQuestionModal')
                ->method('createOrUpdateQuestion')
                ->parameters([
                    'survey_id' => $this->survey->id,
                ])
                ->icon('plus'),
        ];
    }

    /**
     * Лейауты для экрана.
     *
     * @return array
     */
    public function layout(): iterable
    {
        return [
            // Таблица вопросов
            Layout::table('questions', [
                TD::make('id', 'ID')
                    ->width('50px')
                    ->render(function (SurveyQuestion $question) {
                        return $question->id;
                    }),

                TD::make('question_text', 'Вопрос')
                    ->sort()
                    ->filter(TD::FILTER_TEXT)
                    ->style('width: 100px')
                    ->render(function (SurveyQuestion $question) {
                        return $question->question_text;
                    }),

                TD::make('is_multiple', 'Множественный <br> выбор')
                    ->render(function (SurveyQuestion $question) {
                        return $question->is_multiple ? 'Да' : 'Нет';
                    })
                    ->width('100px'),

                TD::make('is_text_answered', 'Текстовый <br>ответ')
                    ->render(function (SurveyQuestion $question) {
                        return $question->is_text_answered ? 'Да' : 'Нет';
                    })->width('50px'),

                TD::make('answers_text', 'Варианты ответов')
                    ->width('170px')
                    ->render(function (SurveyQuestion $question) {
                        $res ='';
                        foreach ($question->answers()->get() as $answer) {
                            $res .= '<div style="float: left">'. $answer->answer_text .'</div>'.

                                Button::make(' ')
                                    ->icon('trash')
                                    ->method('deleteAnswer')
                                    ->parameters([
                                        'answer' => $answer->id,
                                    ])
                                    ->confirm('Вы уверены, что хотите удалить этот ответ?').
                                '<br>';
                        }

                        $res .= ModalToggle::make('Добавить Ответ')
                                ->modal('createOrUpdateAnswerModal')
                                ->method('createOrUpdateAnswer')
                                ->parameters([
                                    'question' => $question->id,
                                ])
                                ->icon('plus');

                        return $res;
                    }),


//                TD::make('answers', 'Варианты Ответов')
//                    ->render(function (SurveyQuestion $question) {
//                        return $question->answers()->count() . ' ответов';
//                    }),

                TD::make('actions', 'Действия')
                    ->align(TD::ALIGN_CENTER)
                    ->width(150)
                    ->render(function (SurveyQuestion $question) {
                        return
                            ModalToggle::make('Редактировать Вопрос')
                                ->modal('createOrUpdateQuestionModal')
                                ->method('createOrUpdateQuestion')
                                ->asyncParameters([
                                    'question' => $question->id,
                                    'survey' => $this->survey->id,
                                ])
                                ->icon('pencil')

                            . ' ' .

                            Button::make('Удалить Вопрос')
                                ->icon('trash')
                                ->method('deleteQuestion')
                                ->parameters([
                                    'question' => $question->id,
                                ])
                                ->confirm('Вы уверены, что хотите удалить этот вопрос?')
                        ;
                    }),
            ]),

            // Модальное окно для создания/редактирования вопроса
            Layout::modal('createOrUpdateQuestionModal', [
                Layout::rows([
                    Input::make('question.id')
                        ->type('hidden'),

                    Input::make('question.survey_id')
                        ->type('hidden')
                        ->value($this->survey->id),

                    TextArea::make('question.question_text')
                        ->title('Текст Вопроса')
                        ->required(),

                    Switcher::make('question.is_multiple')
                        ->title('Разрешить Множественный Выбор')
                        ->sendTrueOrFalse(),

                    Switcher::make('question.is_text_answered')
                        ->title('Разрешить Текстовый Ответ')
                        ->sendTrueOrFalse(),
                ]),
            ])->title('Создать / Редактировать Вопрос')
                ->applyButton('Сохранить')
                ->closeButton('Отмена')
                ->async('asyncGetQuestion'),

            // Модальное окно для создания/редактирования ответа
            Layout::modal('createOrUpdateAnswerModal', [
                Layout::rows([
                    Input::make('answer.id')
                        ->type('hidden'),

                    Input::make('question.id')
                        ->type('hidden'),

                    Matrix::make('matrix')
                        ->columns(['answer_text'])

//                    TextArea::make('answer.answer_text')
//                        ->title('Текст Ответа')
//                        ->required(),
                ]),
            ])->title('Создать / Редактировать Ответ')
                ->async('asyncGetAnswers')
                ->applyButton('Сохранить')
                ->closeButton('Отмена'),

            // Модальное окно подтверждения удаления (можно использовать стандартные Orchid Confirm Modal)
//            \Orchid\Screen\Layouts\Modals\ConfirmModal::class,
        ];
    }

    public function asyncGetAnswers(SurveyQuestion $question):array
    {
        return [
            'answer' => $question->answers(),
//            'matrix' => $question->answers(),
            'question' => $question,
        ];
    }

    /**
     * Асинхронное получение данных вопроса для редактирования.
     *
     * @param SurveyQuestion $question
     * @return array
     */
    public function asyncGetQuestion(SurveyQuestion $question)
    {
        return [
            'question' => $question
        ];
    }

    /**
     * Обработчик создания или обновления вопроса.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function createOrUpdateQuestion(Request $request)
    {
        $questionData = $request->input('question');

        SurveyQuestion::updateOrCreate(
            ['id' => $questionData['id'] ?? null],
            [
                'survey_id' => $questionData['survey_id'],
                'question_text' => $questionData['question_text'],
                'is_multiple' => $questionData['is_multiple'],
                'is_text_answered' => $questionData['is_text_answered'],
            ]
        );

        Alert::info('Вопрос успешно сохранен.');

//        return redirect()->route('platform.survey.questions', $this->survey->id);
    }

    /**
     * Обработчик удаления вопроса.
     *
     * @param Request $request
     */
    public function deleteQuestion(Request $request):void
    {
        $question = SurveyQuestion::findOrFail($request->get('question'));
        $question->delete();

        Alert::info('Вопрос успешно удален.');

        //return redirect()->route('platform.survey.questions', $this->survey->id);
    }

    public function deleteAnswer(Request $request)
    {
        $answer = SurveyOption::findOrFail($request->get('answer'));
        $answer->delete();

        Toast::info('Ответ успешно удален.');
    }
    /**
     * Обработчик создания или обновления ответа.
     *
     * @param array $request
     */
    public function createOrUpdateAnswer(Request $request):void
    {
//        dd($request->all());
        $answerData = $request['answer'];
        $questionData = $request['question'];
        $matrix = $request['matrix'];

        foreach ($matrix as $answer) {
            SurveyOption::updateOrCreate(
                ['id' => $answerData['id'] ?? null],
                [
                    'question_id' => $questionData['id'],
                    'answer_text' => $answer['answer_text'],
                ]
            );
        }

        Alert::info('Ответ успешно сохранен.');

    }
}
