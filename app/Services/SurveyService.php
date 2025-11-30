<?php

namespace App\Services;

use App\Models\Survey;
use App\Models\Sotrudniki;
use Illuminate\Support\Facades\DB;

class SurveyService
{
    /**
     * Получить доступные опросы для пользователя.
     *
     * @param Sotrudniki $user
     * @param string $lang
     * @return \Illuminate\Support\Collection
     */
    public function getAvailableSurveys(Sotrudniki $user, string $lang)
    {
        return Survey::where('lang', $lang)
            ->where(function ($query) use ($user) {
                $query->where('is_all', true)
                    ->orWhereHas('organizations', function ($q) use ($user) {
                        $q->where('organization_structure.id', $user->organization_id);
                    });
            })
            ->whereDoesntHave('responses', function ($query) use ($user) {
                $query->where('sotrudniki_id', $user->id);
            })
            ->select('id', 'title', 'description', 'is_anonymous', 'lang')
            ->get();
    }

    /**
     * Получить все опросы пользователя.
     *
     * @param Sotrudniki $user
     * @return \Illuminate\Support\Collection
     */
    public function getAllSurveys(Sotrudniki $user, $lang)
    {
        return Survey::with(['responses' => function ($query) use ($user, $lang) {
            $query->where('sotrudniki_id', $user->id);
        }]) ->where('lang', $lang)
            ->whereHas('questions')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($survey) use ($user) {
                return [
                    'id' => $survey->id,
                    'title' => $survey->title,
                    'description' => $survey->description,
                    'is_anonymous' => $survey->is_anonymous,
                    'lang' => $survey->lang,
                    'is_completed' => $survey->responses->isNotEmpty(),
                ];
            });
    }

    /**
     * Получить детали конкретного опроса.
     *
     * @param int $surveyId
     * @param string $lang
     * @return Survey|null
     */
    public function getSurveyDetail(int $surveyId)
    {
        return Survey::where('id', $surveyId)
            ->with(['questions.answers'])
            ->first();
    }

    /**
     * Проверить, прошёл ли пользователь опрос с указанным id.
     * Если не прошёл — вернуть сам опрос с вопросами и ответами.
     *
     * @param Sotrudniki $user
     * @param int $surveyId
     * @return array|null Возвращает null если опрос не найден.
     *                   Если найден — массив ['taken' => bool, 'survey' => Survey|null]
     */
    public function getSurveyIfNotTaken(Sotrudniki $user, int $surveyId)
    {
        // Найдём опрос с вопросами и ответами
        $survey = Survey::with(['questions.answers'])->find($surveyId);

        if (!$survey) {
            return null; // опрос не найден
        }

        // Проверим, есть ли ответы этого пользователя на этот опрос
        $taken = $survey->responses()->where('sotrudniki_id', $user->id)->exists();

        return [
            'taken' => (bool) $taken,
            'survey' => $taken ? null : $survey,
        ];
    }

    /**
     * Сохранить ответы пользователя на опрос.
     *
     * @param Sotrudniki $user
     * @param int $surveyId
     * @param array $responses
     * @param array $textResponses
     * @param string $lang
     * @return bool
     */
    public function saveSurveyResponse(Sotrudniki $user, int $surveyId, array $responses, array $textResponses, string $lang)
    {
        return DB::transaction(function () use ($user, $surveyId, $responses, $textResponses, $lang) {
            // Создание записи о прохождении опроса
            $surveyResponse = $user->surveyResponses()->create([
                'survey_id' => $surveyId,
                'lang' => $lang,
            ]);

            // Обработка ответов на вопросы
            foreach ($responses as $questionId => $answerIds) {
                if (is_array($answerIds)) {
                    // Множественный выбор
                    foreach ($answerIds as $answerId) {
                        $surveyResponse->answers()->create([
                            'question_id' => $questionId,
                            'answer_id' => $answerId,
                        ]);
                    }
                } else {
                    // Единственный выбор
                    $surveyResponse->answers()->create([
                        'question_id' => $questionId,
                        'answer_id' => $answerIds,
                    ]);
                }
            }

            // Обработка текстовых ответов
            foreach ($textResponses as $questionId => $text) {
                if ($text) {
                    $surveyResponse->answers()->create([
                        'question_id' => $questionId,
                        'user_text_response' => $text,
                    ]);
                }
            }

            return true;
        });
    }
}
