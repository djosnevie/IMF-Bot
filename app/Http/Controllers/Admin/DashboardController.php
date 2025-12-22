<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Account;
use App\Models\Credit;
use App\Models\WebhookLog;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_conversations' => Conversation::count(),
            'active_conversations' => Conversation::where('status', 'active')->count(),
            'total_messages' => Message::count(),
            'messages_today' => Message::whereDate('created_at', today())->count(),
            'user_messages' => Message::where('sender_type', 'user')->count(),
            'bot_messages' => Message::where('sender_type', 'bot')->count(),
            'webhook_logs_today' => WebhookLog::whereDate('created_at', today())->count(),
            'failed_webhooks_today' => WebhookLog::whereDate('created_at', today())
                ->where('status', 'failed')
                ->count(),
        ];

        $recentConversations = Conversation::with('messages')
            ->orderBy('last_message_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentConversations'));
    }

    public function conversations(Request $request)
    {
        $conversations = Conversation::withCount('messages')
            ->orderBy('last_message_at', 'desc')
            ->paginate(15);

        return view('admin.conversations.index', compact('conversations'));
    }

    public function showConversation($id)
    {
        $conversation = Conversation::with([
            'messages' => function ($query) {
                $query->orderBy('created_at', 'asc');
            }
        ])->findOrFail($id);

        return view('admin.conversations.show', compact('conversation'));
    }

    public function accounts()
    {
        $accounts = Account::orderBy('display_order')->get();
        return view('admin.accounts.index', compact('accounts'));
    }

    public function credits()
    {
        $credits = Credit::orderBy('display_order')->get();
        return view('admin.credits.index', compact('credits'));
    }

    public function logs()
    {
        $logs = WebhookLog::orderBy('created_at', 'desc')->paginate(20);
        return view('admin.logs.index', compact('logs'));
    }
}
