<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetStoriesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'required|integer|exists:stories_category,id',
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Поле идентификатора категории обязательно для заполнения.',
            'category_id.integer' => 'Поле идентификатора категории должно быть целым числом.',
            'category_id.exists' => 'Выбранная категория не существует.',
        ];
    }
}
