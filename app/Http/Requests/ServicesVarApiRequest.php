<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ServicesVarApiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'lang' => 'required|string|in:kz,ru',
        ];
    }

    public function messages()
    {
        return [
            'lang.required' => 'Параметр lang обязателен.',
            'lang.string' => 'Параметр lang должен быть строкой.',
            'lang.in' => 'Недопустимый язык. Допустимые значения: kz, ru.',
        ];
    }
}
