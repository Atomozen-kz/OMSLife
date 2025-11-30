<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Services\N8nService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function __construct(
        private N8nService $n8nService
    ) {}

    public function sendMessage(Request $request): JsonResponse
    {
        $sotrudnik = auth()->user();

        $request->validate([
            'type' => 'required|in:text,audio',
            'content' => 'required_if:type,text|string|max:2000',
            'audio' => 'required_if:type,audio|file|max:10240',
        ]);

        $chat = Chat::firstOrCreate(
            ['sotrudnik_id' => $sotrudnik->id, 'status' => 'active'],
            ['n8n_session_id' => Str::uuid()]
        );

        $messageData = [
            'chat_id' => $chat->id,
            'type' => $request->input('type'),
            'sender' => 'user',
            'audio_file' => null,
            'content' => null
        ];

        if ($request->input('type') === 'text') {
            $messageData['content'] = $request->input('content');
        } else {
            $audioPath = $request->file('audio')->store('chat_audio', 'private');
            $messageData['audio_file'] = $audioPath;
        }

        $message = ChatMessage::create($messageData);

        // Отправка в n8n
        $response = $this->n8nService->sendMessage($chat->n8n_session_id, [
            'session_id' => $chat->n8n_session_id,
            'data' => [
                'message_id' => $message->id,
                'type' => $request->input('type'),
                'content' => $messageData['content'] ?? null,
                'audio_url' => $messageData['audio_file'] ? route('api.chat.audio', $message->id) : null,
                'user_id' => $sotrudnik->id,
                'timestamp' => now()->toISOString(),
            ]
        ]);
        $answer = $this->receiveMessageNow($response);

        return response()->json([
            'message_id' => $message->id,
            'answer_id' => $answer->message_id,
            'status' => 'sent',
            'n8n_response' => $response,
        ]);
    }

    public function receiveMessageNow($n8n_response)
    {
        $chat = Chat::where('n8n_session_id', $n8n_response['session_id'])->firstOrFail();

        $messageData = [
            'chat_id' => $chat->id,
            'type' => 'text',
            'sender' => 'bot',
            'content' =>$n8n_response['answer']
        ];

        $message = ChatMessage::create($messageData);

        if ($n8n_response['type'] === 'audio') {
            $chatMessage = ChatMessage::where('id', $n8n_response['message_id'])->first();
            if ($chatMessage) {
                $chatMessage->update(['content' => $n8n_response['audio_transcribe']]);
            }
        }



        return (object)[
            'message_id' => $message->id,
            'status' => 'received',
        ];
    }


    //Старый метод получения сообщений от n8n через вебхук
    public function receiveMessage(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string',
            'type' => 'required|in:text,audio',
            'content' => 'required_if:type,text|string',
            'audio_url' => 'required_if:type,audio|url',
            'metadata' => 'json',
        ]);

        $chat = Chat::where('n8n_session_id', $request->session_id)->firstOrFail();

        $messageData = [
            'chat_id' => $chat->id,
            'type' => $request->input('type'),
            'sender' => 'bot',
            'metadata' => $request->input('metadata') ? json_decode($request->input('metadata'), true) : null,
        ];

        if ($request->type === 'text') {
            $messageData['content'] = $request->input('content');
        } else {
            // Скачивание аудио с n8n
            $audioContent = file_get_contents($request->audio_url);
            $audioPath = 'chat_audio/' . Str::uuid() . '.mp3';
            Storage::disk('private')->put($audioPath, $audioContent);
            $messageData['audio_file'] = $audioPath;
        }

        $message = ChatMessage::create($messageData);

        return response()->json([
            'message_id' => $message->id,
            'status' => 'received',
        ]);
    }

    public function getChatHistory(Request $request): JsonResponse
            {
                $sotrudnik = auth()->user();
                $sotrudnikId = $sotrudnik->id;

                $perPage = (int) 4;
                $page = (int) $request->input('page', 1);

                $chat = Chat::where('sotrudnik_id', $sotrudnikId)
                    ->where('status', 'active')
                    ->first();

                if (!$chat) {
                    $chat = Chat::firstOrCreate(
                        ['sotrudnik_id' => $sotrudnik->id, 'status' => 'active'],
                        ['n8n_session_id' => Str::uuid()]
                    );
                }

                $messagesQuery = $chat->messages()->orderByDesc('id');
                $paginated = $messagesQuery->paginate($perPage, ['*'], 'page', $page);

                $messages = collect($paginated->items())->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'type' => $message->type,
                        'content' => $message->content,
                        'audio_url' => $message->audio_file ? route('api.chat.audio', $message->id) : null,
                        'sender' => $message->sender,
                        'timestamp' => $message->created_at->toISOString(),
                        'metadata' => $message->metadata,
                    ];
                });

                return response()->json([
                    'chat_id' => $chat->id,
                    'messages' => $messages,
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'total' => $paginated->total(),
                ]);
            }

    public function getAudio($messageId)
    {
        $message = ChatMessage::where('id', $messageId)
            ->whereNotNull('audio_file')
            ->firstOrFail();

        if (!Storage::disk('private')->exists($message->audio_file)) {
            abort(404);
        }

        return Storage::disk('private')->response($message->audio_file);
    }

    public function archiveChat(Request $request, $sotrudnikId): JsonResponse
    {
        $chat = Chat::where('sotrudnik_id', $sotrudnikId)
            ->where('status', 'active')
            ->firstOrFail();

        $chat->update(['status' => 'archived']);

        return response()->json(['status' => 'archived']);
    }
}
