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
            'full_name' => 'Шыракбаев Дамир Алибекович',
            'text' => "Для связи напишите в приложение Aitu по номеру +7 776 063 65 60",
            'photo' => "https://omslife.kz/storage/ceo_photo.png",
            'aitu_url' => 'https://aitu.io/chat/+77760636560',
            'aitu_number' => '+7 776 063 65 60',
            'instagram_url' => '',
        ];

        return response()->json($data);
    }
}

