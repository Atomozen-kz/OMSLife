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
            'sotrudnik.full_name' => 'required|string',
            'sotrudnik.iin' => ['required', 'digits:12', function ($attribute, $value, $fail) {
                // Проверяем, что ИИН содержит корректную дату рождения
                if (!$this->validateIIN($value)) {
                    $fail('ИИН содержит некорректную дату рождения или неверный формат.');
                }
            }],
            'sotrudnik.tabel_nomer' => 'required|integer',
//            'sotrudnik.birthdate' => 'required|date_format:Y-m-d',
//            'sotrudnik.phone_number' => 'required|phone:KZ',
            'sotrudnik.organization_id' => 'required|exists:organization_structure,id|integer',
            'sotrudnik.position_id' => 'required|integer',
        ];
    }

    /**
     * Валидация ИИН
     */
    private function validateIIN($iin): bool
    {
        if (strlen($iin) !== 12 || !is_numeric($iin)) {
            return false;
        }

        // Проверяем дату рождения
        $year = substr($iin, 0, 2);
        $month = substr($iin, 2, 2);
        $day = substr($iin, 4, 2);
        $centuryGender = (int)substr($iin, 6, 1);

        // Определяем век
        $century = null;
        switch ($centuryGender) {
            case 1:
            case 2:
                $century = 1800;
                break;
            case 3:
            case 4:
                $century = 1900;
                break;
            case 5:
            case 6:
                $century = 2000;
                break;
            default:
                return false;
        }

        $fullYear = $century + (int)$year;

        // Проверяем корректность даты
        if (!checkdate((int)$month, (int)$day, $fullYear)) {
            return false;
        }

        return true;
    }

    /**
     * Сообщения об ошибках
     */
    public function messages(): array
    {
        return [
            'sotrudnik.full_name.required' => 'Поле ФИО обязательно для заполнения.',
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
