<?php

namespace App\Http\Requests\orchid;

use Illuminate\Foundation\Http\FormRequest;

class PickupPointRequest extends FormRequest
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
    public function rules()
    {
        return [
            'pickup.name' => 'required|string|max:255',
            'pickup.logo' => 'nullable|string|max:255',
            'pickup.address' => 'required|string|max:500',
            'pickup.is_open' => 'required|boolean',
//            'pickup.working_hours' => 'required|array',
//            'pickup.working_hours.*' => 'nullable|string', // Например: "09:00-18:00" или "closed"
            'pickup.lat' => 'required|numeric|between:-90,90',
            'pickup.lng' => 'required|numeric|between:-180,180',
        ];
    }

    public function messages()
    {
        return [
            'pickup.name.required' => 'Название пункта обязательно.',
            'pickup.address.required' => 'Адрес обязателен.',
//            'pickup.working_hours.required' => 'Режим работы обязателен.',
            'pickup.lat.required' => 'Местоположение  обязательно',
            'pickup.lng.required' => 'Местоположение обязательна.',
            // Добавьте остальные сообщения по необходимости
        ];
    }
}
