<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteCommentRequest extends FormRequest
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
            'comment_id' => 'required|integer|exists:news_comments,id',
        ];
    }

    public function messages()
    {
        return [
            'comment_id.required' => 'Идентификатор комментарий обязателен.',
            'comment_id.integer' => 'Идентификатор комментарий должен быть числом.',
            'comment_id.exists' => 'Комментарий с данным идентификатором не найден.',
        ];
    }
}
