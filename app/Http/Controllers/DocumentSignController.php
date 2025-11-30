<?php

namespace App\Http\Controllers;

use App\Models\SpravkaSotrudnikam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class DocumentSignController extends Controller
{
    //private $documentPath = 'documents/example.pdf'; // Путь к существующему PDF файлу на сервере

    /**
     * Получение SHA256 хеша для PDF файла.
     */

    public function showSignForm($documentId)
    {
        $documentPath = SpravkaSotrudnikam::find($documentId)->pdf_path;
        // Получаем путь к документу
        $documentPath = Storage::disk('public')->url($documentPath);

        return view('sign', [
            'documentUrl' => $documentPath,
            'documentId' => $documentId
        ]);
    }
    public function getHash($documentId)
    {
        $documentPath = SpravkaSotrudnikam::find($documentId)->pdf_path;
        // Получаем путь к документу
//        $fullPath = 'app/'. $documentPath;

        if (!Storage::disk('public')->exists($documentPath)) {
            return response()->json([
                'error' => 'Файл не найден по указанному пути.',
//                '$fullPath' => $fullPath,
                '$documentPath' => $documentPath,
            ], 404);
        }
        $fullPath = Storage::disk('public')->path($documentPath);
        $fileContent = file_get_contents($fullPath);
        $hash = hash('sha256', $fileContent);

        return response()->json([
            'hash' => $hash,
        ]);
    }

    /**
     * Регистрация документа в SIGEX.
     */
    public function registerDocument(Request $request)
    {
        $request->validate([
            'document_path' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $documentPath = $request->input('document_path');
        $description = $request->input('description', 'Документ без описания');

        $documentPath = SpravkaSotrudnikam::find($request->input('document_id'))->pdf_path;

        if (!Storage::disk('public')->exists($documentPath)) {
            return response()->json([
                'error' => 'Файл не найден по указанному пути.',
            ], 404);
        }

        // Получение содержимого файла
        $fileContent = Storage::disk('public')->get($documentPath);

        // Регистрация метаданных документа
        $metadataResponse = Http::post('https://sigex.kz/api', [
            'description' => $description,
        ]);

        if ($metadataResponse->failed()) {
            return response()->json([
                'error' => 'Ошибка при регистрации метаданных документа в SIGEX',
                'details' => $metadataResponse->json(),
            ], $metadataResponse->status());
        }

        $documentId = $metadataResponse->json()['id'];

        // Фиксация содержимого документа
        $dataResponse = Http::post("https://sigex.kz/api/{$documentId}/data", [
            'data' => base64_encode($fileContent),
        ]);

        if ($dataResponse->failed()) {
            return response()->json([
                'error' => 'Ошибка при фиксации содержимого документа в SIGEX',
                'details' => $dataResponse->json(),
            ], $dataResponse->status());
        }

        return response()->json([
            'message' => 'Документ успешно зарегистрирован в SIGEX',
            'document_id' => $documentId,
        ]);
    }

    /**
     * Регистрация подписи в SIGEX API.
     */
    public function registerSignature(Request $request)
    {
        $request->validate([
            'signature' => 'required|string',
        ]);

        $documentId = 'your_document_id'; // Идентификатор документа в SIGEX
        $signature = $request->input('signature');
        $apiUrl = "https://sigex.kz/api/{$documentId}";

        // Отправка подписи в SIGEX
        $response = Http::post($apiUrl, [
            'signature' => $signature,
            'signature_type' => 'CMS',
        ]);

        if ($response->failed()) {
            return response()->json([
                'error' => 'Ошибка при регистрации подписи в SIGEX',
                'details' => $response->json(),
            ], $response->status());
        }

        return response()->json([
            'message' => 'Подпись успешно зарегистрирована',
            'data' => $response->json(),
        ]);
    }

    /**
     * Формирование карточки электронного документа.
     */
    public function buildDocumentCard()
    {
        $documentId = 'your_document_id'; // Идентификатор документа в SIGEX
        $fileName = 'document_card.pdf';

        $apiUrl = "https://sigex.kz/api/{$documentId}/buildDDC";
        $queryParams = http_build_query([
            'fileName' => $fileName,
            'withoutDocumentVisualization' => 'false',
            'withoutSignaturesVisualization' => 'false',
            'withoutQRCodesInSignaturesVisualization' => 'false',
            'withoutID' => 'false',
            'qrWithIDLink' => 'false',
            'language' => 'ru',
        ]);

        $response = Http::post("{$apiUrl}?{$queryParams}");

        if ($response->failed()) {
            return response()->json([
                'error' => 'Ошибка при формировании карточки документа',
                'details' => $response->json(),
            ], $response->status());
        }

        return response()->json([
            'message' => 'Карточка электронного документа успешно сформирована',
            'data' => $response->json(),
        ]);
    }
}
