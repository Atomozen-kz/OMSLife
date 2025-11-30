<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SpravkaSotrudnikamRequest extends FormRequest
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
            'iin' => 'required|string|size:12',
            'organization_id' => 'required|integer|exists:organization_structure,id',
        ];
    }
    /**
     * Сообщения об ошибках.
     */
    public function messages(): array
    {
        return [
            'iin.required' => 'ИИН обязателен.',
            'iin.size' => 'ИИН должен содержать 12 символов.',
            'organization_id.required' => 'Идентификатор организационной структуры обязателен.',
            'organization_id.exists' => 'Организационная структура не найдена.',
        ];
    }
}
