<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    /**
     * Get list of conversations
     */
    public function index(Request $request)
    {
        $conversations = \App\Models\Conversation::with('messages')
            ->orderBy('last_message_at', 'desc')
            ->paginate(20);

        return response()->json($conversations);
    }

    /**
     * Get conversation details with messages
     */
    public function show($id)
    {
        $conversation = \App\Models\Conversation::with([
            'messages' => function ($query) {
                $query->orderBy('created_at', 'asc');
            }
        ])->findOrFail($id);

        return response()->json($conversation);
    }

    /**
     * Get chatbot statistics
     */
    public function stats()
    {
        $stats = [
            'total_conversations' => \App\Models\Conversation::count(),
            'active_conversations' => \App\Models\Conversation::where('status', 'active')->count(),
            'total_messages' => \App\Models\Message::count(),
            'messages_today' => \App\Models\Message::whereDate('created_at', today())->count(),
            'user_messages' => \App\Models\Message::where('sender_type', 'user')->count(),
            'bot_messages' => \App\Models\Message::where('sender_type', 'bot')->count(),
            'webhook_logs_today' => \App\Models\WebhookLog::whereDate('created_at', today())->count(),
            'failed_webhooks_today' => \App\Models\WebhookLog::whereDate('created_at', today())
                ->where('status', 'failed')
                ->count(),
        ];

        return response()->json($stats);
    }
}
