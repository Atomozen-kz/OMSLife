<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadPayrollSlipRequest extends FormRequest
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
            'data' => ['required', 'array'],
            'data.*.last_name' => ['nullable', 'string', 'max:255'],
            'data.*.first_name' => ['nullable', 'string', 'max:255'],
            'data.*.iin' => ['nullable', 'string', 'max:255'],
            'data.*.psp_name' => ['nullable', 'string', 'max:255'],
            'data.*.tabel_nomer' => ['nullable', 'string', 'max:255'],
            'data.*.month' => ['nullable', 'string', 'max:255'],
            'data.*.pdf' => ['required', 'string'], // base64 строка
        ];
    }

    /**
     * Сообщения об ошибках.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'data.required' => 'data не найдено',
            'data.*.last_name.required' => 'Фамилия обязательна.',
            'data.*.first_name.required' => 'Имя обязательна.',
            'data.*.iin.required' => 'ИИН обязательна.',
            'data.*.tabel_nomer.required' => 'Табельный номер обязателен.',
            'data.*.month.required' => 'Месяц обязателен.',
            'data.*.pdf.required' => 'Файл расчетного листа обязателен.'
        ];
    }
}
