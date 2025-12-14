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
            'text' => " ",
            'photo' => "https://omslife.kz/storage/ceo_photo.png",
            'aitu_url' => ' ',
            'instagram_url' => 'https://www.instagram.com/damir_shyrakbayev/',
        ];

        return response()->json($data);
    }
}

