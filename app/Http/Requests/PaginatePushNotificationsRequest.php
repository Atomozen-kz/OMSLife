<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaginatePushNotificationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'page' => 'required|integer|min:1',
            'per_page' => 'required|integer|min:1|max:50',
            'lang' => 'required|string|in:ru,kz',
        ];
    }

    public function messages(): array
    {
        return [
            'page.required' => 'Номер страницы обязателен.',
            'page.integer' => 'Номер страницы должен быть числом.',
            'page.min' => 'Номер страницы должен быть не менее 1.',
            'per_page.required' => 'Количество элементов на странице обязательно.',
            'per_page.integer' => 'Количество элементов на странице должно быть числом.',
            'per_page.min' => 'Количество элементов на странице должно быть не менее 1.',
            'per_page.max' => 'Количество элементов на странице не должно превышать 50.',
            'lang.required' => 'Язык обязателен.',
            'lang.in' => 'Неверный язык. Допустимые значения: ru, kz.',
        ];
    }
}
