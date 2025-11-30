<?php

namespace App\Http\Controllers;

use App\Models\FinancialAssistanceRequest;
use App\Models\FinancialAssistanceType;
use App\Services\FinancialAssistancePdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Tcpdf\Fpdi;

class FinancialAssistanceController extends Controller
{
    protected $pdfService;

    public function __construct(FinancialAssistancePdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Показать полный HTML документ заявления на материальную помощь
     */
    public function showRequestHtml(FinancialAssistanceRequest $request): Response
    {
        // Загружаем связанные данные
        $request->load(['sotrudnik.position', 'sotrudnik.organization', 'assistanceType', 'signer']);

        // Генерируем полный HTML документ
        $fullHtml = $this->pdfService->generateFullHtmlDocument(
            $request,
            $request->sotrudnik,
            $request->signer
        );

        // Обрабатываем плейсхолдеры в контенте
        $processedHtml = $this->pdfService->processTemplateContent(
            $fullHtml,
            $request->form_data ?? [],
            $request->sotrudnik
        );

        return response($processedHtml)
            ->header('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Показать превью HTML документа по типу материальной помощи
     */
    public function showTypePreview(FinancialAssistanceType $type, Request $request): Response
    {
        // Получаем тестовые данные или данные из запроса
        $formData = $request->get('form_data', []);

        // Можно использовать тестового сотрудника или текущего пользователя
        $sotrudnik = auth()->user();

        // Генерируем HTML по типу
        $fullHtml = $this->pdfService->generateHtmlByType(
            $type,
            $sotrudnik,
            null, // Без подписанта для превью
            $formData
        );

        return response($fullHtml)
            ->header('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Показать только центральную часть (контент из БД)
     */
    public function showContentOnly(FinancialAssistanceType $type): Response
    {
        $content = $type->statement_html ?? $type->getDefaultContentTemplate();

        // Простая HTML страница для отображения только контента
        $html = '<!DOCTYPE html>
            <html lang="ru">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Центральная часть шаблона</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        padding: 20px;
                        max-width: 800px;
                        margin: 0 auto;
                        line-height: 1.6;
                    }
                    .info {
                        background: #f0f8ff;
                        padding: 15px;
                        border-left: 4px solid #2196F3;
                        margin-bottom: 20px;
                    }
                    .form-field {
                        margin-bottom: 15px;
                        border: 1px solid #ddd;
                        padding: 10px;
                        background-color: #fafafa;
                    }
                    .field-label {
                        font-weight: bold;
                        margin-bottom: 5px;
                        color: #555;
                    }
                    .field-value {
                        min-height: 20px;
                        border-bottom: 1px dotted #999;
                        padding-bottom: 5px;
                    }
                </style>
            </head>
            <body>
                <div class="info">
                    <strong>Превью центральной части шаблона:</strong> ' . $type->name . '<br>
                    <small>Это только центральная часть документа. Header и footer генерируются автоматически.</small>
                </div>

                <div class="content">
                    ' . $content . '
                </div>
            </body>
            </html>';

        return response($html)
            ->header('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Показать полный документ с тестовыми данными
     */
    public function showFullPreview(FinancialAssistanceType $type): Response
    {
        // Создаем тестовые данные
        $testSotrudnik = (object) [
            'full_name' => 'Иванов Иван Иванович',
            'name' => 'Иванов Иван Иванович',
            'position' => 'Менеджер',
            'department' => 'Отдел разработки',
        ];

        $testSigner = (object) [
            'full_name' => 'Петров Петр Петрович',
            'position' => 'Директор по персоналу',
        ];

        $testFormData = [
            'причина_обращения' => 'Лечение',
            'сумма' => '50000 тенге',
            'период' => 'Сентябрь 2024',
        ];

        // Генерируем полный HTML с тестовыми данными
        $fullHtml = $this->pdfService->generateHtmlByType(
            $type,
            $testSotrudnik,
            $testSigner,
            $testFormData
        );

        return response($fullHtml)
            ->header('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Генерировать PDF документ заявки со всеми прикрепленными файлами
     */
    public function generateRequestPdf(FinancialAssistanceRequest $request)
    {
        // Загружаем связанные данные
        $request->load(['sotrudnik.position', 'sotrudnik.organization', 'assistanceType', 'signer', 'files']);

        // Генерируем HTML для основного документа
        $fullHtml = $this->pdfService->generateFullHtmlDocument(
            $request,
            $request->sotrudnik,
            $request->signer
        );

        // Обрабатываем плейсхолдеры в контенте
        $processedHtml = $this->pdfService->processTemplateContent(
            $fullHtml,
            $request->form_data ?? [],
            $request->sotrudnik
        );

        // Создаем основной PDF
        $pdf = Pdf::setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isPhpEnabled' => true,
            'isRemoteEnabled' => true,
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

        // Возвращаем PDF для скачивания
        return response($mainPdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
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

//        // Добавляем заголовок с информацией о файле
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
            // Добавляем заголовок
            $pdf->AddPage();
            $pdf->SetFont('dejavusans', 'B', 14);
            $pdf->Cell(0, 10, 'Приложение: ' . $file->original_name, 0, 1, 'C');
            $pdf->Ln(5);

            // Импортируем PDF файл
            $pageCount = $pdf->setSourceFile($pdfPath);

            // Добавляем все страницы PDF файла
            for ($i = 1; $i <= $pageCount; $i++) {
                if ($i > 1) {
                    $pdf->AddPage();
                }
                $template = $pdf->importPage($i);
                $pdf->useTemplate($template, 10, $pdf->GetY());
            }
        } catch (\Exception $e) {
            // Если не удалось обработать PDF, добавляем текстовое сообщение
            $pdf->SetFont('dejavusans', '', 12);
            $pdf->Cell(0, 10, 'Ошибка при загрузке PDF: ' . $file->original_name, 0, 1, 'C');
        }
    }
}
