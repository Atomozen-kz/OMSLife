<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class N8nService
{
    private string $webhookUrl;
    private string $apiUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->webhookUrl = config('n8n.webhook_url');
        $this->apiUrl = config('n8n.api_url');
    }

    public function sendMessage(string $sessionId, array $data): array
    {
        try {
            $response = Http::timeout(90)
                ->post($this->webhookUrl, [
                    'session_id' => $sessionId,
                    'data' => $data,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('N8N webhook failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['error' => 'Webhook failed'];

        } catch (\Exception $e) {
            Log::error('N8N webhook exception', [
                'message' => $e->getMessage(),
                'session_id' => $sessionId,
            ]);

            return ['error' => $e->getMessage()];
        }
    }

    public function getWorkflowStatus(string $workflowId): array
    {
        try {
            $response = Http::get("{$this->apiUrl}/workflows/{$workflowId}");

            return $response->json();

        } catch (\Exception $e) {
            Log::error('N8N API exception', [
                'message' => $e->getMessage(),
                'workflow_id' => $workflowId,
            ]);

            return ['error' => $e->getMessage()];
        }
    }
}
