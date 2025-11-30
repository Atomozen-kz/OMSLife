<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'news_id' => 'required|integer|exists:news,id',
            'comment' => 'required|string|max:500',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'news_id.required' => 'Пожалуйста, укажите ID новости.',
            'news_id.exists' => 'Новость с таким ID не найдена.',
            'comment.required' => 'Комментарий обязателен для заполнения.',
            'comment.max' => 'Комментарий не должен превышать 500 символов.',
        ];
    }
}
