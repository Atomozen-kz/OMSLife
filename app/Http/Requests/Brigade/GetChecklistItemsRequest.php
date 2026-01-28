<?php

namespace App\Http\Requests\Brigade;

use Illuminate\Foundation\Http\FormRequest;

class GetChecklistItemsRequest extends FormRequest
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
            'lang' => 'required|in:ru,kz',
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
            'lang.required' => 'Поле язык обязательно для заполнения',
            'lang.in' => 'Неверное значение языка. Допустимые значения: ru, kz',
        ];
    }
}
