<?php

namespace App\Jobs;

use App\Http\Controllers\mobile\PushSotrudnikamController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBrigadeChecklistNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sotrudnikId;
    protected $messageData;

    /**
     * Количество попыток выполнения задачи
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Время ожидания перед повторной попыткой (в секундах)
     *
     * @var int
     */
    public $backoff = 10;

    /**
     * Create a new job instance.
     *
     * @param int $sotrudnikId ID сотрудника
     * @param array $messageData Данные уведомления
     */
    public function __construct(int $sotrudnikId, array $messageData)
    {
        $this->sotrudnikId = $sotrudnikId;
        $this->messageData = $messageData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Генерируем красивый HTML для отчета
            $htmlBody = $this->generateHtmlReport($this->messageData);

            // Создаем копию данных с HTML для базы данных
            $dataForDb = array_merge($this->messageData, [
                'body_html' => $htmlBody,
            ]);

            PushSotrudnikamController::sendPushWithSave(
                $this->sotrudnikId,
                $dataForDb
            );

            Log::info('Push-уведомление успешно отправлено через Job', [
                'sotrudnik_id' => $this->sotrudnikId,
                'notification_type' => 'brigade_checklist',
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при отправке push-уведомления через Job: ' . $e->getMessage(), [
                'sotrudnik_id' => $this->sotrudnikId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Повторно выбрасываем исключение для повторной попытки
            throw $e;
        }
    }

    /**
     * Генерация HTML-отчета для уведомления
     *
     * @param array $messageData
     * @return string
     */
    protected function generateHtmlReport(array $messageData): string
    {
        $data = $messageData['data'] ?? [];
        $sessionId = $data['session_id'] ?? null;
        $masterName = $data['master_name'] ?? 'Не указан';
        $brigadeName = $messageData['brigade_name'] ?? 'Не указана';
        $wellNumber = $messageData['well_number'] ?? 'Не указан';
        $tk = $messageData['tk'] ?? 'Не указан';
        $completedDate = $messageData['completed_at'] ?? now()->format('d.m.Y H:i');

        // Получаем детальные ответы из сессии
        $responses = [];

        if ($sessionId) {
            try {
                $session = \App\Models\BrigadeChecklistSession::with(['responses.checklistItem'])->find($sessionId);
                if ($session) {
                    $responses = $session->responses;
                }
            } catch (\Exception $e) {
                Log::warning('Не удалось получить ответы для сессии: ' . $e->getMessage());
            }
        }

        // Простой HTML без стилей, только цвета для типов ответов
        $html = '
<div>
<p><strong>✅ Чек-лист заполнен</strong></p>
<p>' . htmlspecialchars($completedDate) . '</p>

<p><strong>📋 Общая информация</strong></p>

<p>Мастер (ФИО)<br><strong>' . htmlspecialchars($masterName) . '</strong></p>

<p>Бригада<br><strong>' . htmlspecialchars($brigadeName) . '</strong></p>

<p>Номер скважины<br><strong>' . htmlspecialchars($wellNumber) . '</strong></p>

<p>ТК<br><strong>' . htmlspecialchars($tk) . '</strong></p>

<p><strong>📝 Ответы на вопросы чек-листа</strong></p>';

        // Генерируем список ответов
        if (!empty($responses)) {
            $index = 1;
            foreach ($responses as $response) {
                $itemName = $response->checklistItem->event_name ?? 'Вопрос';
                $responseType = $response->response_type;
                $responseText = $response->response_text;

                // Определяем цвет текста в зависимости от типа ответа
                $textColor = '#333333';
                $badgeText = 'Ответ';

                if ($responseType === 'dangerous') {
                    $textColor = '#dc3545'; // Красный
                    $badgeText = 'Опасно';
                } elseif ($responseType === 'safe') {
                    $textColor = '#28a745'; // Зеленый
                    $badgeText = 'Безопасно';
                } elseif ($responseType === 'other') {
                    $textColor = '#17a2b8'; // Синий
                    $badgeText = 'Другое';
                }

                $html .= '
<p>' . $index . '. ' . htmlspecialchars($itemName) . '
<strong><span style="color:' . $textColor . ';">' . $badgeText . '</span></strong></p>';

                if (!empty($responseText)) {
                    $html .= '<p>' . htmlspecialchars($responseText) . '</p>';
                }

                $index++;
            }
        } else {
            $html .= '<p>Нет данных об ответах</p>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Job SendBrigadeChecklistNotification полностью провалился после всех попыток', [
            'sotrudnik_id' => $this->sotrudnikId,
            'error' => $exception->getMessage(),
        ]);
    }
}

