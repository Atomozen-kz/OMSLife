<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetPushNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|integer|exists:push_sotrudnikam,id',
            'lang' => 'required|string|in:ru,kz',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'ID уведомления обязателен.',
            'id.integer' => 'ID уведомления должно быть числом.',
            'id.exists' => 'Уведомление с таким ID не найдено.',
            'lang.required' => 'Язык обязателен.',
            'lang.in' => 'Неверный язык. Допустимые значения: ru, kz.',
        ];
    }
}
