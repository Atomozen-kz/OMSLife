<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\FinancialAssistanceTypeResource;
use App\Http\Resources\FinancialAssistanceRequestResource;
use App\Models\FinancialAssistanceRequest;
use App\Models\FinancialAssistanceRequestFile;
use App\Models\FinancialAssistanceType;
use App\Models\FinancialAssistanceTypeRow;
use App\Models\Sotrudniki;
use App\Services\FinancialAssistancePdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Tcpdf\Fpdi;

class FinancialAssistanceApiController extends Controller
{
    /**
     * Получить список доступных типов материальной помощи
     */
    public function getTypes(): JsonResponse
    {
        try {
            $types = FinancialAssistanceType::active()
                ->ordered()
                ->with('typeRows')
                ->get();

            return response()->json([
                'success' => true,
                'data' => FinancialAssistanceTypeResource::collection($types),
                'message' => 'Типы материальной помощи получены успешно'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении типов материальной помощи',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить детали конкретного типа материальной помощи
     */
    public function getTypeDetails(int $typeId): JsonResponse
    {
        try {
            $type = FinancialAssistanceType::active()
                ->with('typeRows')
                ->find($typeId);

            if (!$type) {
                return response()->json([
                    'success' => false,
                    'message' => 'Тип материальной помощи не найден или неактивен'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new FinancialAssistanceTypeResource($type),
                'message' => 'Детали типа материальной помощи получены успешно'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении деталей типа материальной помощи',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Подать заявку на материальную помощь
     */
    public function submitRequest(Request $request): JsonResponse
    {
        $sotrudnik = auth()->user(); // Текущий пользователь
        try {
            // Валидация основных данных
            $validator = Validator::make($request->all(), [
                'type_id' => 'required|exists:financial_assistance_types,id',
                'form_data' => 'required|array',
                'form_data.*' => 'required', // Каждое поле формы должно иметь значение
                'files' => 'sometimes|array', // Файлы опциональны
                'files.*' => 'sometimes|array', // Каждое поле может содержать массив файлов
                'files.*.*' => 'file|mimes:jpeg,jpg,png,gif,bmp,webp,pdf|max:10240', // Максимум 10MB
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации данных',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Проверяем, что тип активен
            $type = FinancialAssistanceType::active()->find($request->type_id);
            if (!$type) {
                return response()->json([
                    'success' => false,
                    'message' => 'Выбранный тип материальной помощи неактивен или не существует'
                ], 400);
            }

            // Валидация полей формы и файлов по ID
            $formValidationResult = $this->validateFormFieldsByIds($type, $request->form_data, $request->file('files', []));
            if (!$formValidationResult['success']) {
                return response()->json($formValidationResult, 422);
            }

            // Создаем заявку в транзакции
            DB::beginTransaction();

            $assistanceRequest = FinancialAssistanceRequest::create([
                'id_sotrudnik' => auth()->id(),
                'id_type' => $request->type_id,
                'status' => 1, // На рассмотрении
                'form_data' => $request->form_data,
                'submitted_at' => now(),
            ]);

            // Создаем запись в истории статусов
            $assistanceRequest->statusHistory()->create([
                'new_status' => 1,
                // 'id_user' => auth()->id(),
                'comment' => 'Заявка подана через мобильное приложение',
            ]);

            // Обрабатываем загруженные файлы по ID
            $this->processUploadedFilesByIds($assistanceRequest, $type, $request->file('files', []));

            DB::commit();

            // Генерируем и сохраняем PDF файл заявки
            $pdfService = new \App\Services\FinancialAssistancePdfService();
            $pdfPath = $pdfService->generateAndSavePdf($assistanceRequest);

            return response()->json([
                'success' => true,
                'data' => [
                    'request_id' => $assistanceRequest->id,
                    'status' => $assistanceRequest->status_name,
                    'submitted_at' => $assistanceRequest->submitted_at->format('d.m.Y H:i'),
                    'pdf_url' => $pdfPath ? Storage::disk('public')->url($pdfPath) : null,
                    'pdf_generated' => $pdfPath !== null,
                ],
                'message' => 'Заявка на материальную помощь успешно подана'
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при подаче заявки на материальную помощь',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить список заявок текущего пользователя
     */
    public function getUserRequests(): JsonResponse
    {
        try {
            $requests = FinancialAssistanceRequest::with(['assistanceType', 'signer', 'files'])
                ->where('id_sotrudnik', auth()->id())
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => FinancialAssistanceRequestResource::collection($requests),
                'message' => 'Список заявок получен успешно'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении списка заявок',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить детали конкретной заявки
     */
    public function getRequestDetails(int $requestId): JsonResponse
    {
        try {
            $request = FinancialAssistanceRequest::with(['assistanceType', 'signer', 'statusHistory.changedBy', 'files'])
                ->where('id_sotrudnik', auth()->id())
                ->find($requestId);

            if (!$request) {
                return response()->json([
                    'success' => false,
                    'message' => 'Заявка не найдена или у вас нет доступа к ней'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new FinancialAssistanceRequestResource($request),
                'message' => 'Детали заявки получены успешно'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении деталей заявки',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Валидация полей формы по ID (новый метод)
     */
    private function validateFormFieldsByIds(FinancialAssistanceType $type, array $formData, array $filesByRowId = []): array
    {
        $errors = [];

        foreach ($type->typeRows as $field) {
            $rowId = (string)$field->id;
            $fieldValue = $formData[$rowId] ?? null;

            // Для полей типа "file" проверяем наличие файлов
            if ($field->type === 'file') {
                $fieldFiles = $filesByRowId[$rowId] ?? [];

                if ($field->required && empty($fieldFiles)) {
                    $errors[$field->name] = "Необходимо загрузить файл для поля '{$field->name}'";
                }

                // Валидация файлов
                if (!empty($fieldFiles) && is_array($fieldFiles)) {
                    foreach ($fieldFiles as $file) {
                        if ($file) {
                            $fileValidation = $this->validateFile($file, $field->name);
                            if (!$fileValidation['success']) {
                                $errors[$field->name] = $fileValidation['message'];
                                break;
                            }
                        }
                    }
                }
                continue;
            }

            // Проверка обязательных полей
            if ($field->required && empty($fieldValue)) {
                $errors[$field->name] = "Поле '{$field->name}' обязательно для заполнения";
                continue;
            }

            // Валидация по типу поля
            if (!empty($fieldValue)) {
                switch ($field->type) {
                    case 'date':
                        if (!$this->isValidDate($fieldValue)) {
                            $errors[$field->name] = "Поле '{$field->name}' должно содержать корректную дату в формате dd.mm.yyyy";
                        }
                        break;

                    case 'text':
                        if (strlen($fieldValue) > 255) {
                            $errors[$field->name] = "Поле '{$field->name}' не должно превышать 255 символов";
                        }
                        break;

                    case 'textarea':
                        if (strlen($fieldValue) > 5000) {
                            $errors[$field->name] = "Поле '{$field->name}' не должно превышать 5000 символов";
                        }
                        break;
                }
            }
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Ошибка валидации полей формы',
                'errors' => $errors
            ];
        }

        return ['success' => true];
    }

    /**
     * Валидация полей формы (старый метод для обратной совместимости)
     */
    private function validateFormFields(FinancialAssistanceType $type, array $formData, array $filesByField = []): array
    {
        $errors = [];

        foreach ($type->typeRows as $field) {
            $fieldName = $field->name;
            $fieldValue = $formData[$fieldName] ?? null;

            // Для полей типа "file" проверяем наличие файлов
            if ($field->type === 'file') {
                $fieldFiles = $filesByField[$fieldName] ?? [];

                if ($field->required && empty($fieldFiles)) {
                    $errors[$fieldName] = "Необходимо загрузить файл для поля '{$fieldName}'";
                }

                // Валидация файлов
                if (!empty($fieldFiles) && is_array($fieldFiles)) {
                    foreach ($fieldFiles as $file) {
                        if ($file) {
                            $fileValidation = $this->validateFile($file, $fieldName);
                            if (!$fileValidation['success']) {
                                $errors[$fieldName] = $fileValidation['message'];
                                break;
                            }
                        }
                    }
                }
                continue;
            }

            // Проверка обязательных полей
            if ($field->required && empty($fieldValue)) {
                $errors[$fieldName] = "Поле '{$fieldName}' обязательно для заполнения";
                continue;
            }

            // Валидация по типу поля
            if (!empty($fieldValue)) {
                switch ($field->type) {
                    case 'date':
                        if (!$this->isValidDate($fieldValue)) {
                            $errors[$fieldName] = "Поле '{$fieldName}' должно содержать корректную дату в формате dd.mm.yyyy";
                        }
                        break;

                    case 'text':
                        if (strlen($fieldValue) > 255) {
                            $errors[$fieldName] = "Поле '{$fieldName}' не должно превышать 255 символов";
                        }
                        break;

                    case 'textarea':
                        if (strlen($fieldValue) > 5000) {
                            $errors[$fieldName] = "Поле '{$fieldName}' не должно превышать 5000 символов";
                        }
                        break;
                }
            }
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Ошибка валидации полей формы',
                'errors' => $errors
            ];
        }

        return ['success' => true];
    }

    /**
     * Проверка корректности даты
     */
    private function isValidDate(string $date): bool
    {
        $formats = ['d.m.Y', 'Y-m-d', 'd/m/Y'];

        foreach ($formats as $format) {
            $dateTime = \DateTime::createFromFormat($format, $date);
            if ($dateTime && $dateTime->format($format) === $date) {
                return true;
            }
        }

        return false;
    }

    /**
     * Валидация загруженного файла
     */
    private function validateFile($file, string $fieldName): array
    {
        if (!$file || !$file->isValid()) {
            return [
                'success' => false,
                'message' => "Файл для поля '{$fieldName}' поврежден или не был загружен"
            ];
        }

        // Проверка размера файла (максимум 10MB)
        if ($file->getSize() > 10 * 1024 * 1024) {
            return [
                'success' => false,
                'message' => "Файл для поля '{$fieldName}' превышает максимальный размер 10MB"
            ];
        }

        // Проверка типа файла
        $allowedMimes = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp', 'image/webp',
            'application/pdf'
        ];

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return [
                'success' => false,
                'message' => "Файл для поля '{$fieldName}' должен быть изображением (JPEG, PNG, GIF, BMP, WebP) или PDF документом"
            ];
        }

        return ['success' => true];
    }

    /**
     * Обработка загруженных файлов по ID (новый метод)
     */
    private function processUploadedFilesByIds(FinancialAssistanceRequest $request, FinancialAssistanceType $type, array $filesByRowId): void
    {
        if (empty($filesByRowId)) {
            return;
        }

        // Создаем мапу ID -> поле для быстрого доступа
        $rowsMap = [];
        foreach ($type->typeRows as $row) {
            $rowsMap[$row->id] = $row;
        }

        foreach ($filesByRowId as $rowId => $files) {
            if (!is_array($files) || !isset($rowsMap[$rowId])) {
                continue;
            }

            $typeRow = $rowsMap[$rowId];

            foreach ($files as $file) {
                if (!$file || !$file->isValid()) {
                    continue;
                }

                // Генерируем уникальное имя файла
                $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

                // Сохраняем файл
                $storedPath = $file->storeAs('financial_assistance/' . $request->id, $fileName, 'public');

                // Создаем запись в БД
                FinancialAssistanceRequestFile::create([
                    'id_request' => $request->id,
                    'field_name' => $typeRow->name, // Сохраняем имя поля для обратной совместимости
                    'row_id' => $rowId, // Новое поле - ID строки
                    'file_path' => $storedPath,
                    'original_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getMimeType(),
                    'file_size' => $file->getSize()
                ]);
            }
        }
    }

    /**
     * Обработка загруженных файлов (старый метод для обратной совместимости)
     */
    private function processUploadedFiles(FinancialAssistanceRequest $request, array $filesByField): void
    {
        if (empty($filesByField)) {
            return;
        }

        foreach ($filesByField as $fieldName => $files) {
            if (!is_array($files)) {
                continue;
            }

            foreach ($files as $file) {
                if (!$file || !$file->isValid()) {
                    continue;
                }

                // Генерируем уникальное имя файла
                $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

                // Сохраняем файл
                $storedPath = $file->storeAs('financial_assistance/' . $request->id, $fileName, 'public');

                // Создаем запись в БД
                FinancialAssistanceRequestFile::create([
                    'id_request' => $request->id,
                    'field_name' => $fieldName,
                    'file_path' => $storedPath,
                    'original_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getMimeType(),
                    'file_size' => $file->getSize()
                ]);
            }
        }
    }

    /**
     * Получить сохраненный PDF файл заявки
     */
    public function getSavedRequestPdf(int $requestId): JsonResponse
    {
        try {
            $assistanceRequest = FinancialAssistanceRequest::findOrFail($requestId);
            
            // Проверяем права доступа
            if ($assistanceRequest->id_sotrudnik !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'У вас нет прав для доступа к этой заявке'
                ], 403);
            }

            // Проверяем, есть ли сохраненный PDF
            if (!$assistanceRequest->pdf_path || !Storage::disk('public')->exists($assistanceRequest->pdf_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'PDF файл не найден'
                ], 404);
            }

            // Возвращаем URL для скачивания
            $downloadUrl = Storage::disk('public')->url($assistanceRequest->pdf_path);

            return response()->json([
                'success' => true,
                'data' => [
                    'download_url' => $downloadUrl,
                    'file_name' => basename($assistanceRequest->pdf_path),
                    'generated_at' => $assistanceRequest->updated_at->format('d.m.Y H:i')
                ],
                'message' => 'PDF файл готов к скачиванию'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Заявка не найдена'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error getting saved PDF', [
                'request_id' => $requestId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении PDF файла'
            ], 500);
        }
    }

    /**
     * Генерировать и скачать PDF заявки
     */
    public function downloadRequestPdf(int $requestId): JsonResponse
    {
        try {
            $request = FinancialAssistanceRequest::with(['assistanceType', 'signer', 'files', 'sotrudnik'])
                ->where('id_sotrudnik', auth()->id())
                ->find($requestId);

            if (!$request) {
                return response()->json([
                    'success' => false,
                    'message' => 'Заявка не найдена или у вас нет доступа к ней'
                ], 404);
            }

            // Создаем экземпляр PDF сервиса
            $pdfService = new FinancialAssistancePdfService();

            // Генерируем HTML для основного документа
            $processedHtml = $pdfService->generateFullHtmlDocument(
                $request,
                $request->sotrudnik,
                $request->signer
            );

            // Создаем основной PDF
            $pdf = Pdf::setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isPhpEnabled' => true,
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'isFontSubsettingEnabled' => true,
            ])->loadHTML($processedHtml);

            // Генерируем имя файла
            $fileName = 'financial_assistance_request_' . $request->id . '_' . date('Y_m_d_H_i_s') . '.pdf';
            $filePath = 'financial_assistance/pdf/' . $fileName;

            // Сохраняем основной PDF
            $mainPdfContent = $pdf->output();

            // Если есть прикрепленные файлы, объединяем их с основным PDF
            if ($request->files->count() > 0) {
                $mainPdfContent = $this->mergeFilesToPdf($mainPdfContent, $request->files);
            }

            // Сохраняем финальный PDF
            Storage::disk('public')->put($filePath, $mainPdfContent);

            // Генерируем URL для скачивания
            $downloadUrl = Storage::disk('public')->url($filePath);

            return response()->json([
                'success' => true,
                'data' => [
                    'download_url' => $downloadUrl,
                    'file_name' => $fileName,
                    'file_size' => strlen($mainPdfContent),
                ],
                'message' => 'PDF успешно сгенерирован'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при генерации PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Объединить прикрепленные файлы с основным PDF
     */
    private function mergeFilesToPdf(string $mainPdfContent, $attachedFiles): string
    {
        // Сохраняем основной PDF во временный файл
        $tempMainPdf = tempnam(sys_get_temp_dir(), 'main_pdf_');
        file_put_contents($tempMainPdf, $mainPdfContent);

        // Используем FPDI для объединения файлов
        $pdf = new Fpdi();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        try {
            // Импортируем основной PDF
            $pageCount = $pdf->setSourceFile($tempMainPdf);

            // Добавляем страницы основного документа
            for ($i = 1; $i <= $pageCount; $i++) {
                $pdf->AddPage();
                $template = $pdf->importPage($i);
                $pdf->useTemplate($template);
            }

            // Добавляем прикрепленные файлы
            foreach ($attachedFiles as $file) {
                if ($file->isImage()) {
                    // Добавляем изображение как новую страницу
                    $this->addImageToPdf($pdf, $file);
                } elseif ($file->isPdf()) {
                    // Добавляем страницы PDF файла
                    $this->addPdfToPdf($pdf, $file);
                }
            }

            $result = $pdf->Output('', 'S');
        } finally {
            // Удаляем временный файл
            if (file_exists($tempMainPdf)) {
                unlink($tempMainPdf);
            }
        }

        return $result;
    }

    /**
     * Добавить изображение в PDF как новую страницу
     */
    private function addImageToPdf(Fpdi $pdf, $file): void
    {
        $imagePath = Storage::disk('public')->path($file->file_path);

        if (!file_exists($imagePath)) {
            return;
        }

        // Добавляем новую страницу
        $pdf->AddPage();

        // Добавляем заголовок с информацией о файле
//        $pdf->SetFont('dejavusans', 'B', 14);
//        $pdf->Cell(0, 10, 'Приложение: ' . $file->original_name, 0, 1, 'C');
//        $pdf->Ln(5);

        // Получаем размеры изображения и страницы
        $pageWidth = $pdf->getPageWidth() - 20; // отступы по 10мм с каждой стороны
        $pageHeight = $pdf->getPageHeight() - 40; // отступы сверху и снизу

        try {
            $imageSize = getimagesize($imagePath);
            if ($imageSize) {
                $imageWidth = $imageSize[0];
                $imageHeight = $imageSize[1];

                // Вычисляем пропорции для масштабирования
                $scaleX = $pageWidth / $imageWidth;
                $scaleY = $pageHeight / $imageHeight;
                $scale = min($scaleX, $scaleY, 1); // не увеличиваем изображение

                $scaledWidth = $imageWidth * $scale;
                $scaledHeight = $imageHeight * $scale;

                // Центрируем изображение
                $x = ($pdf->getPageWidth() - $scaledWidth) / 2;
                $y = $pdf->GetY();

                $pdf->Image($imagePath, $x, $y, $scaledWidth, $scaledHeight);
            }
        } catch (\Exception $e) {
            // Если не удалось обработать изображение, добавляем текстовое сообщение
            $pdf->SetFont('dejavusans', '', 12);
            $pdf->Cell(0, 10, 'Ошибка при загрузке изображения: ' . $file->original_name, 0, 1, 'C');
        }
    }

    /**
     * Добавить PDF файл в основной PDF
     */
    private function addPdfToPdf(Fpdi $pdf, $file): void
    {
        $pdfPath = Storage::disk('public')->path($file->file_path);

        if (!file_exists($pdfPath)) {
            return;
        }

        try {
            // Проверяем размер файла
            $fileSize = filesize($pdfPath);
            if ($fileSize === 0) {
                throw new \Exception('PDF файл пустой');
            }

            // Проверяем, что это действительно PDF файл
            $handle = fopen($pdfPath, 'r');
            $header = fread($handle, 4);
            fclose($handle);
            
            if ($header !== '%PDF') {
                throw new \Exception('Файл не является корректным PDF документом');
            }

            // Импортируем PDF файл
            $pageCount = $pdf->setSourceFile($pdfPath);
            
            if ($pageCount === 0) {
                throw new \Exception('PDF файл не содержит страниц');
            }
            
            // Добавляем все страницы PDF файла
            for ($i = 1; $i <= $pageCount; $i++) {
                // Добавляем новую страницу для каждой страницы PDF
                $pdf->AddPage();
                $templateId = $pdf->importPage($i);
                $pdf->useTemplate($templateId);
            }
        } catch (\setasign\Fpdi\PdfParser\StreamReader\StreamReaderException $e) {
            // Специальная обработка для ошибок FPDI связанных с компрессией
            $pdf->AddPage();
            $pdf->SetFont('dejavusans', 'B', 14);
            $pdf->Cell(0, 10, 'Приложение: ' . $file->original_name, 0, 1, 'C');
            $pdf->Ln(5);
            
            $pdf->SetFont('dejavusans', '', 12);
            $pdf->Cell(0, 10, 'PDF файл использует неподдерживаемое сжатие', 0, 1, 'L');
            $pdf->Ln(3);
            $pdf->Cell(0, 10, 'Файл доступен для скачивания отдельно', 0, 1, 'L');
            
            \Log::warning('PDF compression not supported in API controller', [
                'file_name' => $file->original_name,
                'file_path' => $pdfPath,
                'error_message' => $e->getMessage()
            ]);
        } catch (\setasign\Fpdi\PdfParser\PdfParserException $e) {
            // Обработка других ошибок парсера PDF
            $pdf->AddPage();
            $pdf->SetFont('dejavusans', '', 12);
            $pdf->Cell(0, 10, 'Ошибка при обработке PDF: ' . $file->original_name, 0, 1, 'C');
            $pdf->Ln(3);
            $pdf->Cell(0, 10, 'Файл поврежден или имеет неподдерживаемый формат', 0, 1, 'L');
            
            \Log::error('PDF parser error in API controller', [
                'file_name' => $file->original_name,
                'file_path' => $pdfPath,
                'error_message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            // Общая обработка ошибок
            $pdf->AddPage();
            $pdf->SetFont('dejavusans', '', 12);
            $pdf->Cell(0, 10, 'Ошибка при загрузке PDF: ' . $file->original_name, 0, 1, 'C');
            $pdf->Ln(5);
            $pdf->Cell(0, 10, 'Ошибка: ' . $e->getMessage(), 0, 1, 'L');
            
            \Log::error('PDF merge error in API controller', [
                'file_name' => $file->original_name,
                'file_path' => $pdfPath,
                'file_exists' => file_exists($pdfPath),
                'file_size' => file_exists($pdfPath) ? filesize($pdfPath) : 0,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ]);
        }
    }

}
