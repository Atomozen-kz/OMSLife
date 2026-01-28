<?php

namespace App\Http\Requests\Brigade;

use Illuminate\Foundation\Http\FormRequest;

class SubmitChecklistResponseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Проверка авторизации через middleware auth:sanctum
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Общие данные сессии (не дублируются для каждого ответа)
            'well_number' => 'required|string|max:255',
            'tk' => 'required|string|max:255',

            // Массив ответов на вопросы
            'responses' => 'required|array|min:9',
            'responses.*.checklist_item_id' => 'required|exists:brigade_checklist_items,id',
            'responses.*.response_type' => 'required|in:dangerous,safe,other',
            'responses.*.response_text' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            // Валидация общих полей
            'well_number.required' => 'Номер скважины обязателен',
            'well_number.string' => 'Номер скважины должен быть строкой',
            'well_number.max' => 'Номер скважины не может быть длиннее 255 символов',

            'tk.required' => 'ТК обязателен',
            'tk.string' => 'ТК должен быть строкой',
            'tk.max' => 'ТК не может быть длиннее 255 символов',

            // Валидация ответов
            'responses.required' => 'Ответы на чек-лист обязательны',
            'responses.array' => 'Ответы должны быть массивом',
            'responses.min' => 'Необходимо ответить на все 9 вопросов',

            'responses.*.checklist_item_id.required' => 'ID пункта чек-листа обязателен',
            'responses.*.checklist_item_id.exists' => 'Указанный пункт чек-листа не найден',


            'responses.*.response_type.required' => 'Тип ответа обязателен',
            'responses.*.response_type.in' => 'Тип ответа должен быть: dangerous (опасно), safe (безопасно) или other (другое)',

            'responses.*.response_text.string' => 'Текст ответа должен быть строкой',
            'responses.*.response_text.max' => 'Текст ответа не может быть длиннее 1000 символов',
        ];
    }

    /**
     * Дополнительная валидация после основных правил
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $responses = $this->input('responses', []);

            // Проверка, что для типа "other" указан текст ответа
            foreach ($responses as $index => $response) {
                if (isset($response['response_type']) && $response['response_type'] === 'other') {
                    if (empty($response['response_text'])) {
                        $validator->errors()->add(
                            "responses.{$index}.response_text",
                            'Для типа ответа "Другое" необходимо указать текст ответа'
                        );
                    }
                }
            }
        });
    }
}
