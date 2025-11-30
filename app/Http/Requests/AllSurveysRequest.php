<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AllSurveysRequest extends FormRequest
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
            'lang' => 'required|string|in:ru,kz',
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
            'lang.required' => 'Язык обязателен для выбора.',
            'lang.in' => 'Выбранный язык недействителен.',
        ];
    }
}
