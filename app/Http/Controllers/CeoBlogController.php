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
            'text' => "Для связи напишите в приложение Aitu по номеру +7 771 550 30 35",
            'photo' => "https://omslife.kz/storage/ceo_photo.png",
            'aitu_url' => ' ',
            'aitu_number' => '+7 771 550 30 35',
            'instagram_url' => '',
        ];

        return response()->json($data);
    }
}

