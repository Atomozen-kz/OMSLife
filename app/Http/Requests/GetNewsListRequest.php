<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetNewsListRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'lang' => 'required|string|in:ru,kz',
            'page' => 'required|integer',
            'per_page' => 'required|integer',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'lang.required' => 'Пожалуйста, укажите язык.',
            'lang.in' => 'Указан недопустимый язык. Выберите ru или kz.',
            'page.required' => 'Пожалуйста, укажите номер страницы.',
            'page.integer' => 'Номер страницы должен быть целым числом.',
            'per_page.required' => 'Пожалуйста, укажите количество новостей на страницу.',
            'per_page.integer' => 'Количество новостей на страницу должно быть целым числом.',
        ];
    }
}
