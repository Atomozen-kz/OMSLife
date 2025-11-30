<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddPromzonaObjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'id_type' => 'required|exists:promzona_types,id',
            'id_organization' => 'required|exists:organization_structure,id',
            'number' => 'required|string|max:255',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ];
    }

    public function messages(): array
    {
        return [
            'id_type.required' => 'Тип объекта обязателен.',
            'id_type.exists' => 'Выбранный тип объекта недействителен.',
            'id_organization.required' => 'Организация обязательна.',
            'id_organization.exists' => 'Выбранная организация недействительна.',
            'number.required' => 'Номер объекта обязателен.',
            'lat.required' => 'Широта обязательна.',
            'lng.required' => 'Долгота обязательна.',
        ];
    }
}
