<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetCategoryStoriesRequest extends FormRequest
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
            'lang.required' => 'Поле языка обязательно для заполнения.',
            'lang.string' => 'Поле языка должно быть строкой.',
            'lang.in' => 'Выбранный язык недопустим. Должно быть либо "ru", либо "kz".',
        ];
    }
}
