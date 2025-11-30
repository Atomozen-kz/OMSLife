<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class updateFcmTokenRequest extends FormRequest
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
            'fcm_token' => 'required|string',
            'lang' => 'required|string|in:ru,kz',
            'os' => 'nullable|string',
        ];
    }

    public function messages()
    {
        return [
            'fcm_token.required' => 'Поле "fcm_token" обязательно для заполнения.',
            'fcm_token.string' => 'Поле "fcm_token" должно быть строкой.',
            'lang.required' => 'Поле "lang" обязательно для заполнения.',
            'lang.string' => 'Поле "lang" должно быть строкой.',
            'lang.in' => 'Поле "lang" должно быть одним из значений: ru, kz.',
            'os.string' => 'Поле "os" должно быть строкой.',
        ];
    }
}
