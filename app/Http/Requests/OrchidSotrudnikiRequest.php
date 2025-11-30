<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Propaganistas\LaravelPhone\PhoneNumber;

class OrchidSotrudnikiRequest extends FormRequest
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
            'sotrudnik.id' => 'integer|nullable',
            'sotrudnik.last_name' => 'required|string',
            'sotrudnik.first_name' => 'required|string',
            'sotrudnik.father_name' => 'string',
//            'sotrudnik.iin' => 'required|digits:12',
            'sotrudnik.tabel_nomer' => 'required|integer',
//            'sotrudnik.birthdate' => 'required|date_format:Y-m-d',
//            'sotrudnik.phone_number' => 'required|phone:KZ',
            'sotrudnik.organization_id' => 'required|exists:organization_structure,id|integer',
            'sotrudnik.position_id' => 'required|integer',
        ];
    }

    /**
     * Сообщения об ошибках
     */
    public function messages(): array
    {
        return [
            'sotrudnik.first_name.required' => 'Поле Имя обязательно для заполнения.',
            'sotrudnik.last_name.required' => 'Поле Фамилия обязательно для заполнения.',
            'sotrudnik.iin.required' => 'Поле ИИН обязательно для заполнения.',
            'sotrudnik.iin.digits' => 'ИИН должен содержать 12 цифр.',
            'sotrudnik.tabel_nomer.required' => 'Поле табельный номер обязательно для заполнения.',
            'sotrudnik.birthdate.required' => 'Поле дата рождения обязательно для заполнения.',
            'sotrudnik.birthdate.date_format' => 'Дата рождения должна быть в формате ГГГГ-ММ-ДД.',
            'sotrudnik.phone_number.required' => 'Поле номер телефона обязательно для заполнения.',
            'sotrudnik.phone_number.phone' => 'Номер телефона должен быть корректным для Казахстана.',
            'sotrudnik.organization_id.required' => 'Поле структура обязательно для заполнения.',
            'sotrudnik.organization_id.exists' => 'Выбранная структура не существует.',
            'sotrudnik.position_id.required' => 'Поле должность обязательно для заполнения.',
        ];
    }
    protected function prepareForValidation(): void
    {
        // Преобразуем номер телефона в формат E164 до сохранения
        if ($this->has('sotrudnik.phone_number')) {
            $this->merge([
                'sotrudnik' => array_merge($this->sotrudnik, [
                    'phone_number' => phone($this->sotrudnik['phone_number'], 'KZ')->formatE164(),
                ]),
            ]);
        }
    }
}
