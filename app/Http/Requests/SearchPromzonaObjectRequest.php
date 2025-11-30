<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchPromzonaObjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'id_type' => 'nullable|exists:promzona_types,id',
            'id_organization' => 'nullable|exists:organization_structure,id',
            'number' => 'nullable|string|max:255',
            'lang'=>'nullable|string|max:2',
        ];
    }

    public function messages(): array
    {
        return [
            'id_type.required' => 'Тип объекта обязателен.',
            'id_type.exists' => 'Выбранный тип объекта недействителен.',
            'id_organization.required' => 'Организация обязательна.',
            'id_organization.exists' => 'Выбранная организация недействительна.',
            'lang.max' => 'Язык передается неправильно.',
        ];
    }
}
