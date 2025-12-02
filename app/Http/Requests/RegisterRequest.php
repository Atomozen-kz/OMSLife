<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'iin' => 'required|string|size:12',
            'tabel_nomer' => 'required|integer',
            'phone_number' => 'required|phone:KZ',
        ];
    }

    public function messages(): array
    {
        return [
            'iin.required' => 'ИИН обязателен для заполнения',
            'iin.size' => 'ИИН должен содержать 12 символов',
            'tabel_nomer.required' => 'Табельный номер обязателен для заполнения',
            'tabel_nomer.integer' => 'Табельный номер должен быть числом',
            'phone_number.required' => 'Номер телефона обязателен для заполнения',
            'phone_number.phone' => 'Номер телефона должен быть корректным для Казахстана',
        ];
    }
}
