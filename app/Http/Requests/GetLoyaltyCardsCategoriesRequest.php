<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetLoyaltyCardsCategoriesRequest extends FormRequest
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
            'lang' => 'required|string|in:ru,kk',
        ];
    }

    public function messages(): array
    {
        return [
            'lang.required' => 'Пожалуйста, укажите язык.',
            'lang.in' => 'Указан недопустимый язык. Выберите ru или kz.',
        ];
    }
}
