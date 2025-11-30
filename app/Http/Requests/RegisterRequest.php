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
            'last_name' => 'required|string',
            'tabel_nomer' => 'required|integer',
            'organization_id' => 'required|integer',
            'phone_number' => 'required|phone:KZ',
        ];
    }

    public function messages(): array
    {
        return [
            'last_name.required' => 'Фамилия/ неправильно введен',
            'organization_id.required' => 'Организация не выбран',
            'tabel_nomer.required' => 'Табель номер отсуствует/ неправильно введен',
            'phone_number.required' => 'Номер телефона отсуствует/ неправильно введен',
            'phone_number.phone' => 'Номер телефона должен быть корректным для Казахстана.',
        ];
    }
}
