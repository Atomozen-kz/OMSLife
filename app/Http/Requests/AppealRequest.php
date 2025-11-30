<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppealRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Если нужно ограничить доступ, измените это
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'id_topic' => 'required|exists:appeal_topics,id',
            'lang' => 'required|string|in:ru,kz,kk', // Проверка на соответствие языка
            'media.*' => 'nullable|file|mimes:jpeg,png,jpg,mp4,pdf,doc,docx,xls,xlsx,ppt,pptx|max:51200', // Проверка для медиафайлов
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'Поле "Название" обязательно для заполнения.',
            'description.required' => 'Поле "Описание" обязательно для заполнения.',
            'id_topic.required' => 'Необходимо выбрать тему обращения.',
            'id_topic.exists' => 'Выбранная тема обращения не существует.',
            'media.*.mimes' => 'Файлы должны быть формата jpeg, png, jpg, pdf, doc, docx, xls, xlsx, ppt, pptx или mp4.',
            'media.*.max' => 'Размер каждого файла не должен превышать 50 МБ.',
            'lang.required' => 'Язык обязателен.',
        ];
    }
}
