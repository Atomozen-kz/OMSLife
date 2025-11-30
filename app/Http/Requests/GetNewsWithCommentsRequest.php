<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetNewsWithCommentsRequest extends FormRequest
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
            'news_id' => 'required|integer|exists:news,id',
        ];
    }
    public function messages(): array
    {
        return [
            'news_id.required' => 'Пожалуйста, укажите ID новости',
            'news_id.exists' => 'Новость с таким ID не найдена'
        ];
    }
}
