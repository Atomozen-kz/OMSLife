<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class getNewNotificationsCountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'lang' => 'required|string|in:ru,kz',
        ];
    }

    public function messages(): array
    {
        return [
            'lang.required' => 'Язык обязателен.',
            'lang.in' => 'Неверный язык. Допустимые значения: ru, kz.',
        ];
    }
}
