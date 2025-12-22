<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\mobile\PushSotrudnikamController;
use App\Http\Requests\DetailPayrollSlipRequest;
use App\Http\Requests\ListPayrollSlipsRequest;
use App\Http\Requests\UploadPayrollSlipRequest;
use App\Jobs\SendPushNotification;
use App\Models\OrganizationStructure;
use App\Models\PayrollRequest;
use App\Models\PayrollSlip;
use App\Models\PayrollSlip_404;
use App\Models\Sotrudniki;
use App\Services\PushkitNotificationService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Spatie\PdfToImage\Pdf;

class PayrollSlipController extends Controller
{
    /**
     * Обработка загрузки расчетного листа.
     *
     * @param UploadPayrollSlipRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(UploadPayrollSlipRequest $request)
    {
        $payrollData = $request->validated()['data'];
        $startTime = now(); // Время начала запроса

        return DB::transaction(function () use ($payrollData, $startTime, $request) {
            // Создаем запись запроса с начальными значениями
            $payrollRequest = PayrollRequest::create([
                'organization_id' => null, // Определим позже из первого найденного сотрудника
//                'organization_name_in_request' => $payrollData[0]['psp_name'] ?? 'Не указано',
                'find_count' => 0,
                'not_find_count' => 0,
                'created_at' => $startTime,  // время начала запроса
                'updated_at' => $startTime,  // первоначально совпадает с created_at
            ]);

            $findCount = 0;
            $notFindCount = 0;
            $organizationId = null;

            foreach ($payrollData as $data) {
                $sotrudnik = null;

                // Поиск сотрудника по ИИН
                if (!empty($data['iin'])) {
                    $sotrudnik = Sotrudniki::where('iin', $data['iin'])
                        ->first();
                }
//                if (!$sotrudnik){
//                    if (!empty($data['tabel_nomer']) && !empty($data['last_name']) && !empty($data['first_name'])) {
//                        // Поиск сотрудника по табельному номеру и ФИО
//                        $sotrudnik = Sotrudniki::where('tabel_nomer', $data['tabel_nomer'])
//                            ->whereRaw('LOWER(last_name) = ?', [strtolower($data['last_name'])])
//                            ->whereRaw('LOWER(first_name) = ?', [strtolower($data['first_name'])])
//                            ->first();
//                    }
//                }


                if ($sotrudnik && isset($data['pdf'])) {
                    if ($organizationId === null) {
                        $org = OrganizationStructure::find($sotrudnik->organization_id);
                        while ($org && $org->parent_id !== null) {
                            $org = OrganizationStructure::find($org->parent_id);
                        }
                        $organizationId = $org ? $org->id : $sotrudnik->organization_id;
                    }

                    $findCount++;

                    // Декодирование PDF из base64
                    $pdfContent = base64_decode($data['pdf']);

                    // Генерация уникального имени файла
                  $rawName = trim($data['full_name'] ?? $sotrudnik->full_name ?? ' - ');
                        if ($rawName === '') {
                            $rawName = ' - ';
                        }
                        $sanitizedName = preg_replace('/[^a-zA-Zа-яА-Я0-9_\-]/u', '_', $rawName);
                        $sanitizedMonth = preg_replace('/[^a-zA-Zа-яА-Я0-9_\-]/u', '_', ($data['month'] ?? 'Не найдено'));
                        $fileName = 'payroll_slips/' . 'Жировка ' . $sanitizedName . '/' .
                            $sanitizedName . '_' . $sanitizedMonth . '_' . time() . '_' . rand(1111, 9999) . '.pdf';

                    try {
                        Storage::put($fileName, $pdfContent);
                    } catch (\Exception $e) {
                        Log::error('Ошибка при сохранении файла: ' . $e->getMessage());
                        return response()->json([
                            'success' => false,
                            'message' => 'Ошибка при сохранении файла.',
                        ], 500);
                    }

                    try {
                        $payrollSlip = PayrollSlip::create([
                            'sotrudniki_id' => $sotrudnik->id,
                            'last_name' => $sotrudnik->full_name,
                            'first_name' => $sotrudnik->full_name,
                            'father_name' => $sotrudnik->full_name,
                            'tabel_nomer' => $sotrudnik->tabel_nomer,
                            'iin' => $data['iin'] ?? '',
                            'month' => $data['month'] ?? ' - ',
                            'pdf_path' => $fileName,
                        ]);

                        // Отправка уведомления сотруднику
                        if (!empty($sotrudnik->fcm_token)) {
                            // единое сообщение для всех пользователей этой загрузки
                            $messageDataForAll = [
                                'title' => 'Новый расчетный лист',
                                'body' => 'У вас доступен новый расчетный лист за ' . ($data['month'] ?? ' - '),
                                'image' => null,
                                'data' => [
                                    'page' => '/payslip',
                                    'id' => $payrollSlip->id,
                                ],
                                'channelId' => 'payroll_slip',
                            ];

                            if ($sotrudnik->os === Sotrudniki::OS['harmony']) {
                                $tokens[0] = $sotrudnik->fcm_token;
                                $service = new PushkitNotificationService();
                                $service->dispatchPushNotification($messageDataForAll, $tokens, 5);
                            } else {
                                if (!isset($batchTokens)) {
                                    $batchTokens = [];
                                }
                                $batchTokens[] = $sotrudnik->fcm_token;
                                // Сохраняем messageData один раз (перекроет одинаковые значения)
                                $batchMessageData = $messageDataForAll;
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error('Ошибка при сохранении записи в БД: ' . $e->getMessage());
                        Log::error('Данные: ' . json_encode($data));
                        return response()->json([
                            'success' => false,
                            'message' => 'Ошибка при сохранении данных.',
                        ], 500);
                    }
                } else {
                    $notFindCount++;
                    PayrollSlip_404::create([
                        'tabel_nomer' => $data['tabel_nomer'] ?? ' - ',
                        'full_name' => $data['full_name'] ?? ' - ',
                        'month' => $data['month'] ?? ' - ',
                        'iin' => $data['iin'] ?? ' - ',
                        'pdf' => null,
                    ]);
                }
            }

            // Обновляем запись запроса итоговыми значениями и устанавливаем updated_at = now() (время окончания обработки)
            $payrollRequest->update([
                'organization_id' => $organizationId,
                'find_count' => $findCount,
                'not_find_count' => $notFindCount,
                'updated_at' => now(), // время окончания обработки запроса
            ]);

            // После обработки всех записей — запланируем отправку пачек по 50
            if (!empty($batchTokens)) {
                $chunks = array_chunk($batchTokens, 50);
                $delaySeconds = 20; // первая пачка через 20 секунд
                foreach ($chunks as $index => $chunk) {
                    $delay = now()->addSeconds($delaySeconds * ($index + 1));
                    \App\Jobs\SendPushNotificationBatch::dispatch($chunk, $batchMessageData)->delay($delay);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Расчетный лист успешно загружен и уведомление отправлено.',
            ]);
        });
    }

    /**
     * Скачать PDF-файл расчетного листа.
     *
     * @param DetailPayrollSlipRequest $request
     * @param int $id
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function download(DetailPayrollSlipRequest $request)
    {
        // Получение аутентифицированного сотрудника
        $sotrudnik = auth()->user();
        $id = $request->input('id');
        // Поиск расчетного листа и проверка принадлежности сотруднику
        $payrollSlip = PayrollSlip::where('id', $id)
            ->where('sotrudniki_id', $sotrudnik->id)
            ->first();

        if (!$payrollSlip) {
            return response()->json([
                'success' => false,
                'message' => 'Расчетный лист не найден или доступ запрещен.',
            ], 404);
        }

        // Проверка существования файла
        if (!Storage::exists($payrollSlip->pdf_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Файл расчетного листа не найден.',
            ], 404);
        }

        $payrollSlip->is_read = true;
        $payrollSlip->save();

        // Возвращение файла для скачивания
        return Storage::download($payrollSlip->pdf_path, 'Расчетный_лист_' . $payrollSlip->month . '.pdf');
    }

    public function downloadJpg(DetailPayrollSlipRequest $request)
    {
        // Получение аутентифицированного сотрудника
        $sotrudnik = auth()->user();
        $id = $request->input('id');

        // Поиск расчетного листа и проверка принадлежности сотруднику
        $payrollSlip = PayrollSlip::where('id', $id)
            ->where('sotrudniki_id', $sotrudnik->id)
            ->first();

        if (!$payrollSlip) {
            return response()->json([
                'success' => false,
                'message' => 'Расчетный лист не найден или доступ запрещен.',
            ], 404);
        }

        // Проверка существования файла
        if (!Storage::exists($payrollSlip->pdf_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Файл расчетного листа не найден.',
            ], 404);
        }

        // Пути к файлам
        $pdfPath = storage_path('app/private/' . $payrollSlip->pdf_path);
        $jpgPath = storage_path('app/private/' . $payrollSlip->pdf_path . '.jpg');

        try {
            $pdf = new Pdf($pdfPath);
            $pdf
                ->save($jpgPath);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при конвертации PDF в JPG: ' . $e->getMessage(),
            ], 500);
        }

        $payrollSlip->is_read = true;
        $payrollSlip->save();

        // Возвращаем JPG файл для скачивания
        return response()->download($jpgPath, 'Расчетный_лист_' . $payrollSlip->month . '.jpg');
    }

    /**
     * Получить список расчетных листов для сотрудника.
     *
     * @param ListPayrollSlipsRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(ListPayrollSlipsRequest $request)
    {
        // Получение аутентифицированного сотрудника
        $sotrudnik = auth()->user();

        // Получение всех расчетных листов сотрудника, отсортированных по убыванию даты
        $payrollSlips = PayrollSlip::where('sotrudniki_id', $sotrudnik->id)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'month','is_read', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => $payrollSlips,
        ]);
    }


    /**
     * Тестовый метод для загрузки расчетного листа (только запись в лог).
     *
     * @param UploadPayrollSlipRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function test_upload(UploadPayrollSlipRequest $request)
    {
        // Время начала запроса
        $startTime = now();

        // Получаем данные из запроса
        $validated = $request->validated();
        $payrollData = $validated['data'] ?? null;

        // Не логируем поле 'pdf' (большие данные) — создаём копию данных без этого поля
        if (is_array($payrollData)) {
            $sanitizedData = [];
            foreach ($payrollData as $item) {
                if (is_array($item) && array_key_exists('pdf', $item)) {
                    unset($item['pdf']);
                }
                $sanitizedData[] = $item;
            }
            $validatedForLog = $validated;
            $validatedForLog['data'] = $sanitizedData;
        } else {
            $validatedForLog = $validated;
        }

        // Формируем имя файла с датой и временем
        $dateTime = $startTime->format('Y-m-d_H-i-s');
        $fileName = 'payrollslip_testUpload_' . $dateTime . '.log';
        $logPath = storage_path('logs/' . $fileName);

        // Подготовка записи в лог
        $entry = "=== Test upload started at: {$startTime} ===\n";
        $entry .= "Items count: " . (is_array($payrollData) ? count($payrollData) : 0) . "\n";
        $entry .= "Validated request data:\n";
        $entry .= json_encode($validatedForLog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        $entry .= "=== End of entry ===\n\n";

        try {
            // Убедимся, что директория логов существует
            $logDir = dirname($logPath);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }

            // Записываем в файл (добавляем в конец)
            file_put_contents($logPath, $entry, FILE_APPEND | LOCK_EX);
        } catch (\Exception $e) {
            Log::error('Ошибка при записи тестового лога расчетных листов: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при записи в лог-файл: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Тестовые данные успешно записаны в лог.',
            'log_file' => $fileName,
        ]);
    }
}
