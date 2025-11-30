<?php

namespace App\Services;

use App\Models\FinancialAssistanceRequest;
use App\Models\FinancialAssistanceType;
use App\Models\User;
use App\Models\Sotrudniki;
use App\Models\FinancialAssistanceSigner;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Tcpdf\Fpdi;

class FinancialAssistancePdfService
{
    /**
     * Генерировать полный HTML документ для заявления на материальную помощь
     */
    public function generateFullHtmlDocument(
        FinancialAssistanceRequest $request,
        ?Sotrudniki $sotrudniki = null,
        ?FinancialAssistanceSigner $signer = null
    ): string {
        $sotrudniki = $sotrudniki ?? $request->sotrudnik;
        $signer = $signer ?? $request->signer;

        // Обрабатываем содержимое шаблона
        $content = $this->processTemplateContent(
            $request->assistanceType->statement_html ?? $request->assistanceType->getDefaultContentTemplate(),
            $request->form_data ?? [],
            $sotrudniki
        );

        // Используем полный шаблон
        return view('pdf.financial-assistance-pdf', [
            'sotrudnik' => $sotrudniki,
            'signer' => $signer,
            'assistanceType' => $request->assistanceType,
            'contentHtml' => $content,
            'formData' => $request->form_data ?? [],
            'currentDate' => date('d.m.Y'),
            'request' => $request
        ])->render();
    }

    /**
     * Генерировать HTML документ по типу материальной помощи
     */
    public function generateHtmlByType(
        FinancialAssistanceType $type,
        ?Sotrudniki $sotrudnik = null,
        ?FinancialAssistanceSigner $signer = null,
        array $formData = []
    ): string {
        $content = $this->processTemplateContent($type->statement_html ?? $type->getDefaultContentTemplate(), $formData, $sotrudnik);

        $headerData = [
            'sotrudnik' => $sotrudnik,
            'assistance_type' => $type,
            'current_date' => date('d.m.Y'),
            'department' => $sotrudnik->organization->name_ru ?? null,
        ];

        $footerData = [
            'sotrudnik' => $sotrudnik,
            'signer' => $signer,
            'current_date' => date('d.m.Y'),
            'processed_date' => null,
            'request_id' => null,
        ];

        $header = view('pdf.financial-assistance-header', $headerData)->render();
        $footer = view('pdf.financial-assistance-footer', $footerData)->render();

        // Используем полный шаблон вместо конкатенации
        return view('pdf.financial-assistance-pdf', [
            'sotrudnik' => $sotrudnik,
            'signer' => $signer,
            'assistanceType' => $type,
            'contentHtml' => $content,
            'formData' => $formData,
            'currentDate' => date('d.m.Y'),
            'request' => null
        ])->render();
    }

    /**
     * Обработать содержимое шаблона, заменив плейсхолдеры
     */
    public function processTemplateContent(string $template, array $formData = [], ?Sotrudniki $sotrudnik = null): string
    {
        $content = $template;

        // Замена базовых плейсхолдеров
        $replacements = [
            '{{current_date}}' => date('d.m.Y'),
            '{{current_datetime}}' => date('d.m.Y H:i'),
        ];

        // Плейсхолдеры сотрудника
        if ($sotrudnik) {
            $replacements['{{sotrudnik.full_name}}'] = $sotrudnik->full_name ?? '';
            $replacements['{{sotrudnik.position}}'] = $sotrudnik->position->name_ru ?? $sotrudnik->position ?? '';
            $replacements['{{sotrudnik.department}}'] = $sotrudnik->organization->name_ru ?? '';
        }

        // Обработка формы данных
        $formFieldsHtml = $this->generateFormFieldsHtml($formData);
        $replacements['{{form_fields}}'] = $formFieldsHtml;

        // Замена отдельных полей формы
        foreach ($formData as $fieldName => $fieldValue) {
            $placeholder = '{{' . str_replace(' ', '_', strtolower($fieldName)) . '}}';
            $replacements[$placeholder] = $fieldValue;
        }

        // Применяем замены
        foreach ($replacements as $placeholder => $value) {
            $content = str_replace($placeholder, $value, $content);
        }

        // Добавляем шрифт DejaVu Sans ко всем элементам для правильной кодировки
        $content = $this->addFontToHtmlElements($content);

        return $content;
    }

    /**
     * Генерировать HTML для полей формы
     */
    private function generateFormFieldsHtml(array $formData): string
    {
        if (empty($formData)) {
            return '<p><em>Дополнительные поля не заполнены</em></p>';
        }

        $html = '';
        foreach ($formData as $fieldName => $fieldValue) {
            $html .= '<div class="form-field">';
            $html .= '<div class="field-label">' . htmlspecialchars($fieldName) . ':</div>';
            $html .= '<div class="field-value">' . htmlspecialchars($fieldValue) . '</div>';
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Получить данные для header
     */
    public function getHeaderData(
        ?Sotrudniki $sotrudnik = null,
        ?FinancialAssistanceType $assistanceType = null,
        ?FinancialAssistanceRequest $request = null
    ): array {
        return [
            'sotrudnik' => $sotrudnik,
            'assistance_type' => $assistanceType,
            'current_date' => date('d.m.Y'),
            'department' => $sotrudnik->organization->name_ru ?? null,
            'request_id' => $request->id ?? null,
        ];
    }

    /**
     * Получить данные для footer
     */
    public function getFooterData(
        ?Sotrudniki $sotrudnik = null,
        ?FinancialAssistanceSigner $signer = null,
        ?FinancialAssistanceRequest $request = null
    ): array {
        return [
            'sotrudnik' => $sotrudnik,
            'signer' => $signer,
            'current_date' => date('d.m.Y'),
            'processed_date' => $request && $request->processed_at ? $request->processed_at->format('d.m.Y') : null,
            'request_id' => $request->id ?? null,
        ];
    }

    /**
     * Добавить шрифт DejaVu Sans ко всем HTML элементам
     */
    private function addFontToHtmlElements(string $html): string
    {
        // Простой и надежный способ - обернуть весь контент в div с нужным шрифтом
        return '<div style="font-family: \'DejaVu Sans\', sans-serif;">' . $html . '</div>';
    }

    /**
     * Генерировать и сохранить PDF для заявки
     */
    public function generateAndSavePdf(FinancialAssistanceRequest $request): ?string
    {
        try {
            // Загружаем связанные данные
            $request->load(['sotrudnik.position', 'sotrudnik.organization', 'assistanceType', 'signer', 'files']);

            // Генерируем HTML для основного документа
            $processedHtml = $this->generateFullHtmlDocument(
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

            // Обновляем запись в базе данных
            $request->update(['pdf_path' => $filePath]);

            return $filePath;

        } catch (\Exception $e) {
            \Log::error('PDF generation and save error', [
                'request_id' => $request->id,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }

    /**
     * Объединить прикрепленные файлы с основным PDF (копия из контроллера)
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

            // Пытаемся импортировать PDF файл
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
            $this->handleUnsupportedPdfCompression($pdf, $file, $e);
        } catch (\setasign\Fpdi\PdfParser\PdfParserException $e) {
            // Обработка других ошибок парсера PDF
            $this->handlePdfParserError($pdf, $file, $e);
        } catch (\Exception $e) {
            // Общая обработка ошибок
            $this->handleGeneralPdfError($pdf, $file, $e);
        }
    }

    /**
     * Обработка ошибки неподдерживаемого сжатия PDF
     */
    private function handleUnsupportedPdfCompression(Fpdi $pdf, $file, \Exception $e): void
    {
        $pdf->AddPage();
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 10, 'Приложение: ' . $file->original_name, 0, 1, 'C');
        $pdf->Ln(5);
        
        $pdf->SetFont('dejavusans', '', 12);
        $pdf->Cell(0, 10, 'PDF файл использует неподдерживаемое сжатие', 0, 1, 'L');
        $pdf->Ln(3);
        $pdf->Cell(0, 10, 'Файл доступен для скачивания отдельно:', 0, 1, 'L');
        $pdf->Ln(3);
        
        // Добавляем информацию о файле
        $pdf->SetFont('dejavusans', 'I', 10);
        $pdf->Cell(0, 8, 'Имя файла: ' . $file->original_name, 0, 1, 'L');
        $pdf->Cell(0, 8, 'Размер: ' . $this->formatFileSize($file->file_size), 0, 1, 'L');
        $pdf->Cell(0, 8, 'Тип: ' . $file->file_type, 0, 1, 'L');
        
        // Логируем ошибку
        \Log::warning('PDF compression not supported', [
            'file_name' => $file->original_name,
            'file_path' => $file->file_path,
            'file_size' => $file->file_size,
            'error_message' => $e->getMessage()
        ]);
    }

    /**
     * Обработка ошибок парсера PDF
     */
    private function handlePdfParserError(Fpdi $pdf, $file, \Exception $e): void
    {
        $pdf->AddPage();
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 10, 'Приложение: ' . $file->original_name, 0, 1, 'C');
        $pdf->Ln(5);
        
        $pdf->SetFont('dejavusans', '', 12);
        $pdf->Cell(0, 10, 'Ошибка при обработке PDF файла', 0, 1, 'L');
        $pdf->Ln(3);
        $pdf->Cell(0, 10, 'Возможно, файл поврежден или имеет неподдерживаемый формат', 0, 1, 'L');
        
        // Логируем ошибку
        \Log::error('PDF parser error', [
            'file_name' => $file->original_name,
            'file_path' => $file->file_path,
            'error_message' => $e->getMessage(),
            'error_trace' => $e->getTraceAsString()
        ]);
    }

    /**
     * Общая обработка ошибок PDF
     */
    private function handleGeneralPdfError(Fpdi $pdf, $file, \Exception $e): void
    {
        $pdf->AddPage();
        $pdf->SetFont('dejavusans', '', 12);
        $pdf->Cell(0, 10, 'Ошибка при загрузке PDF: ' . $file->original_name, 0, 1, 'C');
        $pdf->Ln(5);
        $pdf->Cell(0, 10, 'Ошибка: ' . $e->getMessage(), 0, 1, 'L');
        
        // Логируем детальную информацию об ошибке
        \Log::error('PDF merge error in service', [
            'file_name' => $file->original_name,
            'file_path' => $file->file_path,
            'file_exists' => file_exists(Storage::disk('public')->path($file->file_path)),
            'file_size' => $file->file_size,
            'error_message' => $e->getMessage(),
            'error_trace' => $e->getTraceAsString()
        ]);
    }

    /**
     * Форматирование размера файла
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
