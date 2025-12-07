<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetActiveCertificatesRequest;
use App\Http\Requests\SpravkaSotrudnikamRequest;
use App\Jobs\ProcessCertificateRequestJob;
use App\Models\SpravkaSotrudnikam;
use App\Models\Sotrudniki;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SpravkaSotrudnikamController extends Controller
{
    /**
     * Обработка запроса на получение справки.
     *
     * @param SpravkaSotrudnikamRequest $request
     * @return JsonResponse
     */
    public function requestCertificate(): JsonResponse
    {
        $user = auth()->user();

        // Проверка, существует ли уже заявка на этот месяц
        if (SpravkaSotrudnikam::where('sotrudnik_id', $user->id)
            ->where(function ($query) {
                $query->where('status', '!=', 7)
                    ->orWhere('created_at', '>=', Carbon::now()->subDays(30));
            })->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'У вас уже есть активная заявка на справку или вы получили справку за последние 30 дней.',
            ], 400);
        }

        // Создание новой заявки
        try {
            $certificate = SpravkaSotrudnikam::create([
                'iin' => $user->iin,
                'organization_id' => $user->organization_id,
                'sotrudnik_id' => $user->id,
                'status' => 1,
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при создании заявки на справку: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Не удалось создать заявку. Попробуйте позже.',
            ], 500);
        }

        // Запуск задания на обработку заявки
        ProcessCertificateRequestJob::dispatch($certificate);

        return response()->json([
            'success' => true,
            'message' => 'Заявка на справку успешно создана и находится в обработке.',
            'data' => [
                'certificate_id' => $certificate->id,
                'status' => $certificate->status,
            ],
        ]);


    }

    /**
     * Получить список активных заявок.
     *
     * @param GetActiveCertificatesRequest $request
     * @return JsonResponse
     */
    public function getActiveCertificates(GetActiveCertificatesRequest $request): JsonResponse
    {

        $sotrudnik = auth()->user();

        // Определение соответствия статусов
        $statusMap = [
            1 => 'Новый',
            2 => 'В обработке',
            3 => 'На подписание',
            7 => 'Готова',
        ];

        // Получение активных заявок пользователя
        $certificates = SpravkaSotrudnikam::where('sotrudnik_id', $sotrudnik->id)
            ->whereIn('status', [1, 2, 3, 7]) // Можно уточнить статусы, если нужны только активные
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($certificate) use ($statusMap) {
                return [
                    'id' => $certificate->id,
                    'name' => 'Справка с место работы',
                    'iin' => $certificate->iin,
                    'organization_id' => $certificate->organization_id,
                    'sotrudnik_id' => $certificate->sotrudnik_id,
                    'status' => $certificate->status,
                    'status_text' => $statusMap[$certificate->status] ?? 'Неизвестный статус',
                    'pdf_path' => ($certificate->status !== 7) ? null : url(Storage::url($certificate->ddc_path)),
                    'created_at' => $certificate->created_at->format('d.m.Y H:i'),
                ];
            });

        // Формирование ответа
        return response()->json([
            'success' => true,
            'data' => $certificates,
        ]);
    }
}
