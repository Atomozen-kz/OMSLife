<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SurveyDetailRequest extends FormRequest
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
        return [
            'id' => 'required|integer|exists:surveys,id',
        ];
    }

    /**
     * Сообщения об ошибках.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'id.required' => 'Идентификатор опроса обязателен.',
            'id.integer' => 'Идентификатор опроса должен быть числом.',
            'id.exists' => 'Опрос с данным идентификатором не найден.',
        ];
    }
}
