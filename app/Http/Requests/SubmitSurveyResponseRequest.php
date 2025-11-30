<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\Survey;

class SubmitSurveyResponseRequest extends FormRequest
{
    /**
     * Определение, авторизован ли пользователь для выполнения этого запроса.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user() !== null;
    }

    /**
     * Получение правил валидации для запроса.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [];

        $surveyId = $this->route('id');
        $survey = Survey::with('questions.answers')->find($surveyId);

        if ($survey) {
            foreach ($survey->questions as $question) {
                $field = 'responses.' . $question->id;

                // Если вопрос с множественным выбором
                if ($question->is_multiple) {
                    if ($question->is_text_answered) {
                        // делаем выбор обязательным только если нет текстового ответа (и текстовый ответ непустой)
                        $rules[$field] = [
                            'nullable',
                            Rule::requiredIf(function () use ($question) {
                                $textResponses = $this->input('text_responses', []);
                                $val = $textResponses[$question->id] ?? null;
                                return empty($val);
                            }),
                            'array',
                            // проверяем min только когда текст пуст
                            function ($attribute, $value, $fail) use ($question) {
                                $textResponses = $this->input('text_responses', []);
                                $val = $textResponses[$question->id] ?? null;
                                if (empty($val)) {
                                    if (!is_array($value) || count($value) < 1) {
                                        $fail('Вы должны выбрать хотя бы один вариант ответа.');
                                    }
                                }
                            },
                        ];
                    } else {
                        $rules[$field] = ['required', 'array', 'min:1'];
                    }

                    $rules[$field . '.*'] = ['integer', 'exists:surveys_answers,id,question_id,' . $question->id];

                // Если одиночный выбор
                } else {
                    if ($question->is_text_answered) {
                        $rules[$field] = [
                            'nullable',
                            Rule::requiredIf(function () use ($question) {
                                $textResponses = $this->input('text_responses', []);
                                $val = $textResponses[$question->id] ?? null;
                                return empty($val);
                            }),
                            // проверяем значение и существование только если текст пуст
                            function ($attribute, $value, $fail) use ($question) {
                                $textResponses = $this->input('text_responses', []);
                                $val = $textResponses[$question->id] ?? null;
                                if (empty($val)) {
                                    if ($value === null || $value === '') {
                                        $fail('Этот вопрос обязателен для ответа.');
                                        return;
                                    }

                                    if (!is_numeric($value) || intval($value) <= 0) {
                                        $fail('Выбранный вариант ответа недействителен.');
                                        return;
                                    }

                                    $exists = DB::table('surveys_answers')
                                        ->where('id', intval($value))
                                        ->where('question_id', $question->id)
                                        ->exists();

                                    if (!$exists) {
                                        $fail('Выбранный вариант ответа не существует.');
                                    }
                                }
                            },
                        ];
                    } else {
                        $rules[$field] = ['required', 'integer', 'exists:surveys_answers,id,question_id,' . $question->id];
                    }
                }

                if ($question->is_text_answered) {
                    $rules['text_responses.' . $question->id] = ['nullable', 'string', 'max:1000'];
                }
            }
        }

        return $rules;
    }

    /**
     * Сообщения об ошибках.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'responses.*.required' => 'Этот вопрос обязателен для ответа.',
            'responses.*.required_without' => 'Этот вопрос обязателен для ответа.',
            'responses.*.array' => 'Ответ должен быть массивом.',
            'responses.*.min' => 'Вы должны выбрать хотя бы один вариант ответа.',
            'responses.*.*.integer' => 'Выбранный вариант ответа недействителен.',
            'responses.*.*.exists' => 'Выбранный вариант ответа не существует.',
            // Для одиночного (не-массивного) ответа — дополнительные ключи
            'responses.*.integer' => 'Выбранный вариант ответа недействителен.',
            'responses.*.exists' => 'Выбранный вариант ответа не существует.',
            'text_responses.*.string' => 'Текстовый ответ должен быть строкой.',
            'text_responses.*.max' => 'Текстовый ответ слишком длинный.',
        ];
    }
}
