<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class CeoBlogController extends Controller
{
    /**
     * Возвращает JSON для "блога генерального директора".
     * Поля: full_name, text, photo, aitu_url, instagram_url
     * Не использует базу данных — данные жёстко прописаны.
     */
    public function index(): JsonResponse
    {
        $data = [
            'full_name' => 'Алексей Петров',
            'text' => "Добро пожаловать в блог генерального директора. Здесь будут опубликованы важные сообщения и размышления о развитии компании и общества.",
            'photo' => url('/storage/ceo/photo.jpg'),
            'aitu_url' => 'https://aitu.example.com/alexey.petrov',
            'instagram_url' => 'https://instagram.com/alexey.petrov',
        ];

        return response()->json($data);
    }
}

