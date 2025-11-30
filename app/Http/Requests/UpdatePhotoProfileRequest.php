<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePhotoProfileRequest extends FormRequest
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
            'photo_profile' => 'required|image|mimes:jpeg,png,jpg,gif|max:4096'
        ];
    }

    public function messages(): array
    {
        return [
            'photo_profile.required' => 'Необходимо загрузить фото профиля.',
            'photo_profile.image' => 'Загружаемый файл должен быть изображением.',
            'photo_profile.mimes' => 'Поддерживаются только форматы: jpeg, png, jpg, gif.',
            'photo_profile.max' => 'Максимальный размер файла: 4MB.',
        ];
    }
}
