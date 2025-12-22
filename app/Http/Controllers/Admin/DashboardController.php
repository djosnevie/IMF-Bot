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

    public function createAccount()
    {
        return view('admin.accounts.create');
    }

    public function storeAccount(Request $request)
    {
        $data = $request->validate([
            'reference' => 'required|string|unique:accounts',
            'display_name' => 'required|string|max:255',
            'account_type' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'currency' => 'required|string|max:3',
            'interest_rate' => 'nullable|string',
            'initial_deposit' => 'nullable|string',
            'maintenance_fee' => 'nullable|string',
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ]);

        Account::create($data);

        return redirect()->route('admin.accounts')->with('success', 'Compte créé avec succès.');
    }

    public function editAccount($id)
    {
        $account = Account::findOrFail($id);
        return view('admin.accounts.edit', compact('account'));
    }

    public function updateAccount(Request $request, $id)
    {
        $account = Account::findOrFail($id);

        $data = $request->validate([
            'reference' => 'required|string|unique:accounts,reference,' . $id,
            'display_name' => 'required|string|max:255',
            'account_type' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'currency' => 'required|string|max:3',
            'interest_rate' => 'nullable|string',
            'initial_deposit' => 'nullable|string',
            'maintenance_fee' => 'nullable|string',
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ]);

        $account->update($data);

        return redirect()->route('admin.accounts')->with('success', 'Compte mis à jour avec succès.');
    }

    public function destroyAccount($id)
    {
        Account::findOrFail($id)->delete();
        return redirect()->route('admin.accounts')->with('success', 'Compte supprimé avec succès.');
    }

    public function credits()
    {
        $credits = Credit::orderBy('display_order')->get();
        return view('admin.credits.index', compact('credits'));
    }

    public function createCredit()
    {
        return view('admin.credits.create');
    }

    public function storeCredit(Request $request)
    {
        $data = $request->validate([
            'reference' => 'required|string|unique:credits',
            'display_name' => 'required|string|max:255',
            'amount_range' => 'nullable|string',
            'duration_range' => 'nullable|string',
            'interest_rate' => 'nullable|string',
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ]);

        Credit::create($data);

        return redirect()->route('admin.credits')->with('success', 'Crédit créé avec succès.');
    }

    public function editCredit($id)
    {
        $credit = Credit::findOrFail($id);
        return view('admin.credits.edit', compact('credit'));
    }

    public function updateCredit(Request $request, $id)
    {
        $credit = Credit::findOrFail($id);

        $data = $request->validate([
            'reference' => 'required|string|unique:credits,reference,' . $id,
            'display_name' => 'required|string|max:255',
            'amount_range' => 'nullable|string',
            'duration_range' => 'nullable|string',
            'interest_rate' => 'nullable|string',
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ]);

        $credit->update($data);

        return redirect()->route('admin.credits')->with('success', 'Crédit mis à jour avec succès.');
    }

    public function destroyCredit($id)
    {
        Credit::findOrFail($id)->delete();
        return redirect()->route('admin.credits')->with('success', 'Crédit supprimé avec succès.');
    }

    public function logs()
    {
        $logs = WebhookLog::orderBy('created_at', 'desc')->paginate(20);
        return view('admin.logs.index', compact('logs'));
    }

    public function users()
    {
        $users = \App\Models\User::orderBy('name')->get();
        return view('admin.users.index', compact('users'));
    }

    public function createUser()
    {
        return view('admin.users.create');
    }

    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        \App\Models\User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($data['password']),
        ]);

        return redirect()->route('admin.users')->with('success', 'Utilisateur créé avec succès.');
    }

    public function destroyUser($id)
    {
        if ($id == auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        \App\Models\User::findOrFail($id)->delete();
        return redirect()->route('admin.users')->with('success', 'Utilisateur supprimé avec succès.');
    }
}
