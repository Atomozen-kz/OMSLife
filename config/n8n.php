<?php
// config/n8n.php
return [
'webhook_url' => env('N8N_WEBHOOK_URL', 'http://localhost:5678/webhook/your-webhook-id'),
'api_url' => env('N8N_API_URL', 'http://localhost:5678/api/v1'),
];
