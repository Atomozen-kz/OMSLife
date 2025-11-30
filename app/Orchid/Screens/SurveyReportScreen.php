<?php

namespace App\Orchid\Screens;

use App\Models\Survey;
use App\Models\SurveyResponseAnswer;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class SurveyReportScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     */

    private Survey $survey;

    private $result;
    private array $tableSources = []; // имена источников для Layout::table
    public function query(Survey $survey) : array
    {
        $this->survey = $survey;

        $questions = $this->survey->questions()
            ->with(['answers.responses', 'responses.surveyResponse.sotrudniki'])
            ->get();

        $result = $questions->map(function ($question) {
            return [
                'question_id' => $question->id,
                'question' => $question->question_text,
                'responses' => $question->answers->map(function ($answer) {
                    return [
                        'answer_text' => $answer->answer_text,
                        'count' => $answer->responses->count(),
                    ];
                }),
                'user_text_responses' => $question->responses->filter(function ($response) {
                    return !is_null($response->user_text_response);
                })->map(function ($response) {
                    return [
                        'text' => $response->user_text_response,
                        'sotrudnik' => $response->surveyResponse && $response->surveyResponse->sotrudniki
                            ? $response->surveyResponse->sotrudniki->fio
                            : 'Неизвестный сотрудник',
                    ];
                })->values()->toArray(),
            ];
        });

        $this->result = $result->toArray();

        $charts = array();
        foreach ($result as $key => $item) {
            $r = array();
            if (count($item['responses']) > 0) {
                $r['name'] = $item['question'];
                foreach ($item['responses'] as $answer) {
                    $r['labels'][] = $answer['answer_text'];
                    $r['values'][] = $answer['count'];
                }
            }

            $charts['chart_'.$item['question_id']][] = $r;
        }
        // Табличные данные по каждому вопросу
        $tablesPayload = [];
        $this->tableSources = []; // очистим на всякий случай

        foreach ($this->result as $item) {
            $sourceKey = 'table_' . $item['question_id'];
            $this->tableSources[] = ['key' => $sourceKey, 'title' => $item['question']];

            $total = $item['responses']->sum('count') ?: 1;

            $rows = $item['responses']->map(function ($resp) use ($total) {
                return [
                    'option'  => $resp['answer_text'],
                    'count'   => $resp['count'],
                    'percent' => round($resp['count'] * 100 / $total, 1),
                ];
            })->toArray();

            $tablesPayload[$sourceKey] = $rows;
        }
//        dd($tablesPayload);
//
        // Возвращаем: опрос, графики и динамические источники таблиц
        return array_merge([
            'survey' => $this->survey,
            'charts' => $charts,
        ], $tablesPayload);
    }

    /**
     * The name of the screen is displayed in the header.
     */
    public function name(): ?string
    {
        return "Отчет по опросу: {$this->survey->title}";
    }

    public $description = 'Просмотр и анализ результатов опроса';


    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar() : array
    {
        return [];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout() : array
    {
        $layouts = array();
        foreach ($this->result as $key => $item) {
            if (count($item['responses']) > 0){
                $layouts[] =

                    $this->getChartLayout('charts.chart_'.$item['question_id'], $item['question']);
            }

            // таблица цифр
            $sourceKey = 'table_' . $item['question_id'];

            $table = Layout::table($sourceKey, [
                TD::make('option', 'Вариант ответа')
                    ->width('60%')
                    ->render(fn(array $row) => $row['option']),

                TD::make('count', 'Кол-во')
                    ->align(TD::ALIGN_CENTER)->width('20%')
                    ->render(fn(array $row) => $row['count']),

                TD::make('percent', '%')
                    ->align(TD::ALIGN_CENTER)->width('20%')
                    ->render(fn(array $row) => $row['percent'].' %'),
            ]);

            // если нужен заголовок над таблицей — используем Block
            $layouts[] = Layout::block($table)
                ->title($item['question'])
                ->description('Распределение ответов по вариантам');

            if (count($item['user_text_responses']) > 0){
                $layouts[] =

                        Layout::view('partials.text-answers', [
                            'responses' => $item['user_text_responses'],
                            'question' => $item['question'],
                            ]);
            }

        }
        return [
            $layouts
        ];
    }

    private function getChartLayout($target, $name)
    {
        return Layout::chart($target, $name)
            ->type('bar') // Линейный график
            ->export(true) // Включаем экспорт графика
            ->height(350); // Высота графика
    }

}
