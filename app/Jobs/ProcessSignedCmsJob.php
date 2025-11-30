<?php
namespace App\Jobs;

use App\Models\SpravkaSotrudnikam;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessSignedCmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $cmsFilePath;
    public $spravka;

    /**
     * Create a new job instance.
     *
     * @param string $cmsFilePath
     * @param SpravkaSotrudnikam $spravka
     */
    public function __construct(string $cmsFilePath, SpravkaSotrudnikam $spravka)
    {
        $this->cmsFilePath = $cmsFilePath;
        $this->spravka = $spravka;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            // Вызов метода processSignedCms
            $this->processSignedCms($this->cmsFilePath, $this->spravka);
        } catch (Exception $e) {
            Log::error('Ошибка при обработке подписанного файла: ' . $e->getMessage());
        }
    }

    /**
     * Process the signed CMS file.
     *
     * @param string $cmsFilePath
     * @param SpravkaSotrudnikam $spravka
     */
    private function processSignedCms(string $cmsFilePath, SpravkaSotrudnikam $spravka)
    {
        $certificatePath = storage_path("app/signed_certificates/{$spravka->id}.pem");

        // Извлечение сертификата через OpenSSL
//        exec("openssl cms -verify -in " . storage_path("app/{$cmsFilePath}") . " -inform DER -noverify -certsout {$certificatePath}");
        $parsedCert = openssl_x509_parse($certificatePath);
        // Анализ сертификата
        $certificateInfo = shell_exec("openssl x509 -in {$certificatePath} -text -noout");

        // Разбор данных сертификата
        $parsedData = $this->parseCertificateInfo($certificateInfo);

        // Сохранение данных в модель SpravkaSotrudnikam
        $spravka->update([
            'signed_by' => $parsedData['signed_by'],
            'iin' => $parsedData['iin'],
            'signed_at' => now(),
            'certificate_serial' => $parsedData['serial_number'],
        ]);
    }

    /**
     * Parse certificate information.
     *
     * @param string $certificateInfo
     * @return array
     */
    private function parseCertificateInfo(string $certificateInfo): array
    {
        preg_match('/Subject:.*CN=(.*),.*IIN=(\d+)/', $certificateInfo, $matches);
        preg_match('/Serial Number:\s*([a-f0-9]+)/i', $certificateInfo, $serialMatches);

        return [
            'signed_by' => $matches[1] ?? 'Неизвестно',
            'iin' => $matches[2] ?? 'Неизвестно',
            'serial_number' => $serialMatches[1] ?? 'Неизвестно',
        ];
    }
}
