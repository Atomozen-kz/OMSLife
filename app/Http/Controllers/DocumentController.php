<?php

namespace App\Http\Controllers;

use App\Http\Controllers\mobile\PushSotrudnikamController;
use App\Jobs\ProcessSignedCmsJob;
use App\Jobs\SendPushNotification;
use App\Models\Sotrudniki;
use App\Models\SpravkaSotrudnikam;
use App\Services\PushkitNotificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use Adapik\CMS\Exception\FormatException;
use Adapik\CMS\UnsignedAttribute;
use Falseclock\AdvancedCMS\OCSPResponse;
use Falseclock\AdvancedCMS\RevocationValues;
use Falseclock\AdvancedCMS\SignedData;
use Falseclock\AdvancedCMS\SignedDataContent;
use Falseclock\AdvancedCMS\SignerInfo;
use Falseclock\AdvancedCMS\TimeStampResponse;
use Falseclock\AdvancedCMS\TimeStampToken;
use Falseclock\AdvancedCMS\UnsignedAttributes;
use FG\ASN1\Exception\ParserException;
use FG\ASN1\Universal\Sequence;
use setasign\Fpdi\Tcpdf\Fpdi;
use setasign\Fpdi\PdfReader;

class DocumentController extends Controller
{


    public function generatePdf(Request $request)
    {
        // Получаем данные из запроса
        $data = $request->only([
            'signer_position',
            'signer_fio',
            'text_kz_to_pdf',
            'id_spravka'
        ]);
        // Установка локали для Carbon для правильного форматирования даты
        Carbon::setLocale('kk');
        $spravka = SpravkaSotrudnikam::find($data['id_spravka']);

        $sotrudnik = Sotrudniki::find($spravka->sotrudnik_id);
        // Подготовка данных для шаблона
        $pdfData = [
            'sotrudnik' => (object) $sotrudnik,
            'text_kz' => $data['text_kz_to_pdf'],
            'spravka'=> (object) $spravka->toArray(),
            'signer' => (object)[
                'position' => $data['signer_position'],
                'fio' => $data['signer_fio'],
            ],
            'todayDate' => Carbon::now()->translatedFormat('Y жыл j F'), // Форматируем текущую дату
        ];
        // Генерация PDF
        $pdf = PDF::setOptions([
            'defaultFont' => 'Times New Roman', // Установите Times New Roman по умолчанию
        ])->loadView('pdf.spravka_html_pdf', $pdfData);



        $fileName = 'spravki/'.$spravka->id . '_' .$sotrudnik->full_name . '/Анықтама ' .$sotrudnik->full_name . '.pdf';

        Storage::disk('public')->put($fileName, $pdf->output());

        // Обновление пути к PDF в записи
        $spravka->pdf_path = $fileName;
        $spravka->status = 2;
        $spravka->save();

        return redirect()->route('platform.pdf-view', ['spravka' => $spravka->id]);

        //return response()->json(['spravka' => $spravka->id, 'success' => true]);

        // Возвращаем PDF в браузер
//        return $pdf->stream('spravka.pdf');
    }

    public function saveSignedPdf(Request $request)
    {
        $request->validate([
            'signedData' => 'required|string',
            'spravkaId' => 'required|exists:spravka_sotrudnikam,id',
        ]);

        $spravka = SpravkaSotrudnikam::findOrFail($request->spravkaId);

        // Генерация пути для сохранения CMS файла
        $cmsFilePath = "spravki/{$spravka->id}_{$spravka->sotrudnik->last_name}/{$spravka->id}_{$spravka->sotrudnik->last_name}_{$spravka->sotrudnik->first_name}.cms";

        // Сохранение подписанного документа (пример)
        Storage::disk('public')->put($cmsFilePath, $request->signedData);

//        $signature = base64_encode(file_get_contents(storage_path("app/public/{$cmsFilePath}"))); // Закодировать файл CMS в Base64
//
//        // 1. Регистрация документа в SIGEX
//        $registerResponse = Http::post('https://sigex.kz/api/documents', [
//            'name' => "Документ {$spravka->id}",
//            'description' => "Справка с места работы ({$spravka->sotrudnik->fio})",
//            'signType' => 'cms',
//            'signature' => $signature,
//        ]);
//
//        if ($registerResponse->failed()) {
//            return response()->json(['error' => 'Ошибка регистрации документа в SIGEX'], 500);
//        }
//
//        $documentId = $registerResponse->json('id');


        // Сохранение пути в базе данных
        $spravka->update([
            'signed_path' => $cmsFilePath,
            'signed_at' => now(),
        ]);

        $message_data = array(
            'title' => 'Справка с места работы',
            'body' => 'Справка готова. Можете скачать документ',
            'image' => NULL,
            'data' => [
                'type' => 'spravka',
                'id' => $spravka->id
            ],
            'channelId' => 'congratulation'
        );

//        PushSotrudnikamController::sendPushSotrudniku($spravka->sotrudnik_id, $message_data);
        $sotrudnik = Sotrudniki::find($spravka->sotrudnik_id);
        if($sotrudnik->os === Sotrudniki::OS['harmony']) {
            $tokens[0] = $sotrudnik->fcm_token;
            $service = new PushkitNotificationService();
            $service->dispatchPushNotification($message_data,$tokens,5);
        } else {
            SendPushNotification::dispatch($spravka->sotrudnik_id, $message_data)->delay(now()->addSeconds(5));
        }

        self::adapicCmsGetSgnerInfo($spravka->id);
        $spravka->status = 7;
        $spravka->save();


//        $keyInfo = $this->extractCmsData(storage_path("app/public/{$cmsFilePath}"));
        return response()->json(
            [
                'message' => 'Документ успешно подписан и сохранён.'
            ]);
    }

    function extractCmsData($cmsFilePath)
    {
        $tempFilePath = storage_path('app/extracted_content.pem');
        $customLogPath = storage_path('logs/cms_extraction_'.time().'.log');

// Убедимся, что папка для логов существует
        if (!file_exists(storage_path('logs'))) {
            mkdir(storage_path('logs'), 0777, true);
        }

// Выполняем команду OpenSSL для извлечения данных
        $command = "openssl cms -verify -in $cmsFilePath -inform DER -noverify -out $tempFilePath";
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            // Если произошла ошибка, записываем её в отдельный файл лога
            file_put_contents($customLogPath, "Ошибка при обработке CMS файла:\n" .$command. implode("\n", $output) . "\n\n", FILE_APPEND);
            exit("Ошибка при обработке CMS файла. Подробности в логах: $customLogPath");
        }

// Чтение извлеченного содержимого
        $extractedContent = file_get_contents($tempFilePath);

// Записываем извлечённое содержимое в отдельный файл лога
        file_put_contents($customLogPath, "Извлечённое содержимое CMS файла:\n$extractedContent\n\n", FILE_APPEND);

// Удаляем временный файл
        unlink($tempFilePath);

    }

    public function adapicCmsGetSgnerInfo($spravka_id)
    {
        $spravka = SpravkaSotrudnikam::find($spravka_id);
//        dd($spravka);
        try {
            // Путь к CMS-файлу
            $cmsFilePath = Storage::disk('public')->path($spravka->signed_path);

            // Загружаем содержимое CMS-файла
            $cmsContent = file_get_contents($cmsFilePath);

            if ($cmsContent === false) {
                throw new Exception("Не удалось загрузить CMS файл.");
            }

            // Создаём объект SignedData из содержимого файла
            $signedData = SignedData::createFromContent($cmsContent);

            // Получаем сертификаты из SignedDataContent
            $certificates = $signedData->getSignedDataContent()->getCertificateSet();

            foreach ($certificates as $certificate) {
                $subject = $certificate->getSubject();
                $signature = $certificate->getSignature();

//                var_dump($signature->getIdentifier()->isConstructed());
//                $signature->getStringValue();
//                var_dump($certificate);
//                echo "Фамилия: " . ($subject->getSurname() ?? "Не указано") . PHP_EOL;
//                echo "Имя: " . ($subject->getGivenName() ?? "Не указано") . PHP_EOL;
//                echo "Общее имя (CN): " . ($subject->getCommonName() ?? "Не указано") . PHP_EOL;
//                echo "Общее имя (CN): " . ($subject->getSerialNumber() ?? "Не указано") . PHP_EOL;
//                echo "Общее имя (CN): " . ($subject->getOrganizationName() ?? "Не указано") . PHP_EOL;
//                echo "Общее имя (CN): " . ($subject->getEmailAddress() ?? "Не указано") . PHP_EOL;
//                echo "Общее имя (CN): " . ($subject->getKnowledgeInformation() ?? "Не указано") . PHP_EOL;
//                echo "Общее имя (CN): " . ($subject->getTitle() ?? "Не указано") . PHP_EOL;
//                echo "Общее имя (CN): " . ($subject->getDescription() ?? "Не указано") . PHP_EOL;
//                echo "Общее имя (CN): " . ($subject->getBinary() ?? "Не указано") . PHP_EOL;
//                echo "Общее имя (CN): " . ($subject->getBase64Content() ?? "Не указано") . PHP_EOL;
//
//                echo "<br><br>Сигнатура: ". base64_encode($signature). PHP_EOL;
//                echo "<br><br>getStringValue: ". $signature->getStringValue(). PHP_EOL;
//
//                echo "<br><br>getStringValue: ". $signature->getStringValue(). PHP_EOL;
//                echo "<br><br>getSerial: ". $certificate->getSerial(). PHP_EOL;
//                echo "<br><br>getSubjectKeyIdentifier: ". $certificate->getSubjectKeyIdentifier(). PHP_EOL;
//                echo "<br><br>getSubjectKeyIdentifier: ". $certificate->getValidNotBefore(). PHP_EOL;
//                echo "<br><br>getSubjectKeyIdentifier: ". $certificate->getValidNotAfter(). PHP_EOL;
//                echo "<br><br>Сигнатура: ". $certificate->getIssuer(). PHP_EOL;
//                echo '<br><br>';
                $pechat = array();

                $pechat[] = 'ИС "OMS Life" №'.$spravka->id.' от '.Carbon::make($spravka->signed_at)->format('d.m.Y');
                $pechat[] = $certificate->getIssuer();
                $pechat[] = 'O='.$subject->getOrganizationName();
                $pechat[] = 'CN='.$subject->getCommonName().' '.$subject->getSerialNumber();
                $pechat[] = 'Серийный номер: '.$certificate->getSubjectKeyIdentifier();
                $pechat[] = 'Срок действия: '.date('d.m.Y', strtotime($certificate->getValidNotBefore())).' - '.date('d.m.Y', strtotime($certificate->getValidNotAfter()));
                $pechat[] = 'Подпись: '.$signature;

                return self::modifyPdf($spravka->id, "\n".join("\n", $pechat)."\n\n");
            }

        } catch (FormatException $e) {
            echo "Ошибка формата: " . $e->getMessage() . PHP_EOL;
        } catch (Exception $e) {
            echo "Произошла ошибка: " . $e->getMessage() . PHP_EOL;
        }
    }

    public static function modifyPdf($id_spravki, $sealText)
    {
        $spravka = SpravkaSotrudnikam::find($id_spravki);

        if (!$spravka){
            return response()->exception('Error');
        }

        // Проверьте наличие исходного PDF
        $inputPdfPath = Storage::disk('public')->path($spravka->pdf_path);
        if (!file_exists($inputPdfPath)) {
            die('Исходный PDF файл не найден.');
        }

        // Путь для сохранения нового PDF
        $fileName_ddc = "spravki/{$spravka->id}_{$spravka->sotrudnik->last_name}/ddc_{$spravka->id}_{$spravka->sotrudnik->last_name}_{$spravka->sotrudnik->first_name}.pdf";
        $outputPdfPath = Storage::disk('public')->path($fileName_ddc);

        // Генерация QR-кода
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?data='.Config::get('app.url').'/spravka_proverka/'.$spravka->id;


        $response = Http::get($qrCodeUrl);
//        dd($response->body());
        $fileName = '';
        if ($response->successful()) {
            $fileName = 'qr_codes/' . md5($qrCodeUrl) . '.png';
            Storage::disk('public')->put($fileName, $response->body());
        }

//        $sealText = "
//        ИС \"OMG Life\" №28 от 13.01.2025
//        2.5.4.6: KZ; 2.5.4.3: ҰЛТТЫҚ КУӘЛАНДЫРУШЫ ОРТАЛЫҚ (GOST)
//        O=Акционерное общество \"Озенмунайгаз\"
//        CN=НҰРҒАЛИ НҰРСҰЛТАН IIN921004301996
//        Серийный номер: IIN921004301996
//        Срок действия: 18.03.2024 - 18.03.2025
//        Подпись: 35e1056fd25b0b744bf05040b190a5bc6b362b1bdadaf8ca540a5c1a3ac89a6a03542c84f869a6ea9697380bb2d072a1cd97e8dec23da292e8a8b19e54feb822
//    ";

        // Создание экземпляра FPDI-TCPDF
        $pdf = new Fpdi();

        // Загружаем существующий PDF
        $pageCount = $pdf->setSourceFile($inputPdfPath);
        for ($i = 1; $i <= $pageCount; $i++) {
            $templateId = $pdf->importPage($i);
            $pdf->AddPage();
            $pdf->useTemplate($templateId);
        }
        $pdf->SetFooterMargin(0);
        // Вычисление Y-координаты

        // Добавление QR-кода
        if ($fileName) {
            $qrCodePath = Storage::disk('public')->path($fileName);
            $pdf->Image($qrCodePath, 20, 220, 25); // Y = availableHeight - высота элемента
        }

        // Добавление текста
        $pdf->SetFont('dejavusans', '', 8); // Шрифт для казахских букв
        $pdf->SetTextColor(0, 0, 255); // Синий цвет текста
        $pdf->SetDrawColor(0, 0, 255); // Синий цвет рамки
        $pdf->SetLineWidth(0.3); // Толщина линии рамки
        $pdf->SetXY(100, 210); // Y = availableHeight - высота текста
        $pdf->MultiCell(100, 4, $sealText, 1, 'L');

        // Сохранение нового PDF
        $pdf->Output($outputPdfPath, 'F');

        $spravka->update(['ddc_path' => $fileName_ddc]);

        // Возвращаем файл для скачивания
        return true;
    }


    public function backToEdit(Request $request)
    {
        $spravkaId = $request->input('spravka_id');

        // Найти справку по ID
        $spravka = SpravkaSotrudnikam::find($spravkaId);

        if (!$spravka) {
            return response()->json(['success' => false, 'message' => 'Справка не найдена.']);
        }

        try {
            // Удалить PDF файл
            Storage::disk('public')->delete($spravka->pdf_path);

            // Установить статус справки на 1
            $spravka->status = 1;
            $spravka->pdf_path = null;
            $spravka->save();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }



//    public function modifyPdf()
//    {
//
//        if (!file_exists(resource_path('fonts/DejaVuSans.php'))) {
//            die('Файл DejaVuSans.php не найден');
//        }
//
//        if (!file_exists(resource_path('fonts/DejaVuSans.z'))) {
//            die('Файл DejaVuSans.z не найден');
//        }
//
//
//        // Путь к загруженному PDF
//        $inputPdfPath = storage_path('app\public\spravki\28_Турламбаев\28_Турламбаев_Нурсултан.pdf');
//        $outputPdfPath = storage_path('app/new_document.pdf');
//
//        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?data=dqdqwdqw';
//
//        // Отправляем HTTP-запрос для получения изображения
//        $response = Http::get($qrCodeUrl);
//        $fileName ='';
//        // Проверяем, удалось ли получить QR-код
//        if ($response->successful()) {
//            // Генерируем имя файла
//            $fileName = 'qr_codes/' . md5($qrCodeUrl) . '.png';
//
//            // Сохраняем изображение в хранилище (storage/app/qr_codes)
//            Storage::disk('public')->put($fileName, $response->body());
//
//            // Возвращаем путь к файлу
////            return Storage::url($fileName); // Для доступа через браузер (если storage:link настроен)
//        }
//
//
//        $sealText = "
//        ИС \"OMG Life\" №28 от 13.01.2025<br>
//        2.5.4.6: KZ; 2.5.4.3: ҰЛТТЫҚ КУӘЛАНДЫРУШЫ ОРТАЛЫҚ (GOST)<br>
//        O=Акционерное общество \"Озенмунайгаз\"<br>
//        CN=НҰРҒАЛИ НҰРСҰЛТАН IIN921004301996<br>
//        Серийный номер: IIN921004301996<br>
//        Срок действия: 18.03.2024 - 18.03.2025<br>
//        Подпись: 35e1056fd25b0b744bf05040b190a5bc6b362b1bdadaf8ca540a5c1a3ac89a6a03542c84f869a6ea9697380bb2d072a1cd97e8dec23da292e8a8b19e54feb822
//    ";
////        $encoding = mb_detect_encoding($sealText, mb_detect_order(), true);
////        echo $sealText;
////        return;
////        var_dump(mb_convert_encoding($sealText, 'UTF-8', 'auto'));
////        return;
//        define('FPDF_FONTPATH', resource_path('fonts/'));
//        // Создание экземпляра FPDI
//        $pdf = new TCPDF();
//
//        // Загружаем исходный PDF
//        $pageCount = $pdf->setSourceFile($inputPdfPath);
//
//        // Копируем страницы исходного PDF
//        for ($i = 1; $i <= $pageCount; $i++) {
//            $templateId = $pdf->importPage($i);
//            $pdf->addPage();
//            $pdf->useTemplate($templateId);
//        }
//
//        if ($fileName){
//            // Добавляем изображение (пример: внизу слева)
//            $pdf->Image(Storage::path($fileName), 20, 260, 25); // Координаты и размер изображения
//        }
//
////        $sealText = iconv('UTF-8', 'CP1251//IGNORE', $sealText);
////        $sealText = mb_convert_encoding($sealText, 'UTF-8', 'Windows-1252');
////        // Подключаем шрифт DejaVu Sans
//        $pdf->AddFont('DejaVuSans', '', 'DejaVuSans.php');
//
//        // Добавляем текст с рамкой (внизу справа)
//        $pdf->SetXY(100, 230); // Устанавливаем координаты
//        $pdf->SetFont('DejaVuSans', '', 10); // Устанавливаем шрифт
//        $pdf->SetTextColor(0, 0, 255); // Устанавливаем синий цвет текста
//        $pdf->SetDrawColor(0, 0, 255); // Устанавливаем синий цвет рамки
//        $pdf->SetLineWidth(0.3); // Толщина линии рамки
//        $pdf->MultiCell(100, 4, iconv('utf-8', 'RK1048//IGNORE', $sealText), 1, 'L'); // Добавляем текст с рамкой
////        $pdf->MultiCell(100, 4, mb_convert_encoding($sealText,'UTF-8', 'Windows-1251' ), 1, 'L'); // Добавляем текст с рамкой
////        $pdf->MultiCell(100, 4, $sealText, 1, 'L'); // Добавляем текст с рамкой
//
//        // Сохраняем новый PDF
//        $pdf->Output($outputPdfPath, 'F');
////        MakeFont();
//        // Возвращаем файл для загрузки
//        return response()->download($outputPdfPath);
//    }



    /**
     * Генерация подписанного PDF
     */
    public function generateSignedPdf(SpravkaSotrudnikam $spravka)
    {
        $originalPdfPath = Storage::path($spravka->pdf_path);
        $signedPdfPath = Storage::path("signed_documents/{$spravka->id}_signed.pdf");

        // Проверяем, существует ли оригинальный PDF
        if (!file_exists($originalPdfPath)) {
            abort(404, 'Оригинальный документ не найден');
        }

        // Получаем данные подписанта и QR URL
        $signedBy = $spravka->signed_by ?? 'Неизвестно';
        $position = $spravka->position ?? 'Неизвестна';
        $qrUrl = route('document.verify', ['id' => $spravka->id]);

        // Создаём PDF с TCPDF
        $pdf = new TCPDF();

        // Загрузка оригинального PDF
        $pageCount = $pdf->setSourceFile($originalPdfPath);

        for ($i = 1; $i <= $pageCount; $i++) {
            $tplId = $pdf->importPage($i);
            $pdf->AddPage();
            $pdf->useTemplate($tplId);
        }

        // Добавляем новую страницу с текстом и QR-кодом
        $pdf->AddPage();
        $pdf->SetFont('dejavusans', '', 10);

        // Текст равнозначности
        $pdf->SetXY(10, 20);
        $pdf->MultiCell(0, 10, "Осы құжат «Электрондық құжат және электрондық цифрлық қолтаңба туралы» Қазақстан Республикасының 2003 жылғы 7 қаңтардағы N 370-II Заңы 7 бабының 1 тармағына сәйкес қағаз тасығыштағы құжатпен бірдей\n\nДанный документ согласно пункту 1 статьи 7 ЗРК от 7 января 2003 года N370-II «Об электронном документе и электронной цифровой подписи» равнозначен документу на бумажном носителе", 0, 'L');

        // Текст подписи
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->SetXY(10, 50);
        $pdf->MultiCell(0, 10, "Автор: {$signedBy}\nДолжность: {$position}", 0, 'L');

        // Генерация QR-кода
        $qrImagePath = Storage::path("qr_codes/{$spravka->id}.png");
        \QrCode::format('png')->size(150)->generate($qrUrl, $qrImagePath);

        // Вставляем QR-код в нижний правый угол
        $pdf->Image($qrImagePath, $pdf->getPageWidth() - 50, $pdf->getPageHeight() - 50, 40, 40);

        // Сохраняем PDF
        $pdf->Output($signedPdfPath, 'F');

        // Обновляем модель
        $spravka->update(['signed_pdf_path' => "signed_documents/{$spravka->id}_signed.pdf"]);

        return $signedPdfPath;
    }


















    public function signDocument(Request $request)
    {
        $signedPdfBase64 = $request->input('signedPdf');
        $documentId = $request->input('documentId');

        // Валидация входных данных
        if (!$signedPdfBase64 || !$documentId) {
            return response()->json(['status' => 'error', 'message' => 'Некорректные данные'], 400);
        }

        // Декодирование Base64
        $signedPdfData = base64_decode($signedPdfBase64);

        // Сохранение подписанного PDF-файла
        $fileName = $documentId .' - '.time(). '_signed.cms';
        Storage::disk('public')->put('signed_documents/' . $fileName, $signedPdfData);

        // Обновление статуса документа или другие действия

//        $spravka = SpravkaSotrudnikam::find($documentId);
//
//        $spravka->status = 3;
//        $spravka->save();

        return response()->json(['status' => 'success', 'message' => 'Документ успешно подписан.']);
    }
//
//
//    public function signDocument(Request $request)
//    {
//        $signedData = $request->input('signedData');
//        $documentId = $request->input('documentId');
//
//        // Валидация входных данных
//        if (!$signedData || !$documentId) {
//            return response()->json(['status' => 'error', 'message' => 'Некорректные данные'], 400);
//        }
//
//        // Декодирование Base64
//        $signedDataDecoded = base64_decode($signedData);
//
//        // Сохранение подписанного файла
//        $fileName = $documentId . '_signed.p7s'; // Расширение .p7s для подписи
//        Storage::disk('public')->put('signed_documents/' . $fileName, $signedDataDecoded);
//
//        // Обновление статуса документа или другие действия
//        // ...
//
//        return response()->json(['status' => 'success', 'message' => 'Документ успешно подписан.']);
//    }

    // Остальной код контроллера...
}
