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
        $query = Conversation::withCount('messages')
            ->orderBy('last_message_at', 'desc');

        // Filtrage par scope : si l'utilisateur ne peut pas tout voir,
        // il ne voit que les conversations liées aux plaintes de ses tickets assignés
        if (!auth()->user()->can('conversations.view_all')) {
            $query->whereHas('complaint.ticket', function ($q) {
                $q->where('assigned_to', auth()->id());
            });
        }

        $conversations = $query->paginate(15);

        return view('admin.conversations.index', compact('conversations'));
    }

    public function showConversation($id)
    {
        $conversation = Conversation::with([
            'messages' => function ($query) {
                $query->orderBy('created_at', 'asc');
            }
        ])->where('uuid', $id)->firstOrFail();

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
        $account = Account::where('uuid', $id)->firstOrFail();
        return view('admin.accounts.edit', compact('account'));
    }

    public function updateAccount(Request $request, $id)
    {
        $account = Account::where('uuid', $id)->firstOrFail();

        $data = $request->validate([
            'reference' => 'required|string|unique:accounts,reference,' . $account->id,
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
        Account::where('uuid', $id)->firstOrFail()->delete();
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
        $credit = Credit::where('uuid', $id)->firstOrFail();
        return view('admin.credits.edit', compact('credit'));
    }

    public function updateCredit(Request $request, $id)
    {
        $credit = Credit::where('uuid', $id)->firstOrFail();

        $data = $request->validate([
            'reference' => 'required|string|unique:credits,reference,' . $credit->id,
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
        Credit::where('uuid', $id)->firstOrFail()->delete();
        return redirect()->route('admin.credits')->with('success', 'Crédit supprimé avec succès.');
    }

    public function logs()
    {
        $logs = WebhookLog::orderBy('created_at', 'desc')->paginate(20);
        return view('admin.logs.index', compact('logs'));
    }

    public function users()
    {
        $query = \App\Models\User::orderBy('name');
        
        // Un non-super-admin ne peut pas voir les super-admins
        if (!auth()->user()->hasRole('super-admin')) {
            $query->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'super-admin');
            });
        }
        
        $users = $query->get();
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

    public function editUser($id)
    {
        $user = \App\Models\User::where('uuid', $id)->firstOrFail();
        
        if ($user->hasRole('super-admin') && !auth()->user()->hasRole('super-admin')) {
            abort(403, "Vous ne pouvez pas gérer un compte Super Admin.");
        }
        
        $roles = \Spatie\Permission\Models\Role::all();
        if (!auth()->user()->hasRole('super-admin')) {
            $roles = $roles->reject(function ($role) {
                return $role->name === 'super-admin';
            });
        }
        $permissions = \Spatie\Permission\Models\Permission::all()->groupBy(function($perm) {
            return explode('.', $perm->name)[0];
        });
        
        // La gestion des utilisateurs est réservée aux rôles SuperAdmin et Admin.
        $permissions->forget('users');
        
        return view('admin.users.edit', compact('user', 'roles', 'permissions'));
    }

    public function assignRole(Request $request, $id)
    {
        // Comparer les UUID et non les IDs numériques
        if ($id === auth()->user()->uuid) {
            abort(403, "Vous ne pouvez pas modifier votre propre rôle.");
        }

        $user = \App\Models\User::where('uuid', $id)->firstOrFail();
        
        if ($user->hasRole('super-admin') && !auth()->user()->hasRole('super-admin')) {
            abort(403, "Vous ne pouvez pas gérer un compte Super Admin.");
        }

        // Empêcher un Admin d'assigner le rôle super-admin à quelqu'un
        if ($request->role === 'super-admin' && !auth()->user()->hasRole('super-admin')) {
            abort(403, "Vous n'avez pas l'autorisation d'assigner le rôle Super Admin.");
        }

        $data = $request->validate([
            'role' => 'nullable|string|exists:roles,name',
        ]);

        if (empty($data['role'])) {
            $user->syncRoles([]);
            $user->syncPermissions([]);
        } else {
            $user->syncRoles([$data['role']]);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->route('admin.users.edit', $user->uuid)->with('success', 'Rôle mis à jour avec succès.');
    }

    public function syncPermissions(Request $request, $id)
    {
        if ($id === auth()->user()->uuid) {
            abort(403, "Vous ne pouvez pas modifier vos propres permissions.");
        }

        $user = \App\Models\User::where('uuid', $id)->firstOrFail();

        if ($user->roles->isEmpty()) {
            return back()->with('error', 'Impossible d\'assigner des permissions : cet utilisateur n\'a aucun rôle.');
        }

        if ($user->hasRole('super-admin') && !auth()->user()->hasRole('super-admin')) {
            abort(403, "Vous ne pouvez pas gérer un compte Super Admin.");
        }

        $data = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $permissions = collect($data['permissions'] ?? [])->reject(function ($perm) {
            return str_starts_with($perm, 'users.');
        })->toArray();
        
        $user->syncPermissions($permissions);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->route('admin.users.edit', $user->uuid)->with('success', 'Permissions individuelles mises à jour avec succès.');
    }

    public function destroyUser($id)
    {
        if ($id === auth()->user()->uuid) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $user = \App\Models\User::where('uuid', $id)->firstOrFail();
        if ($user->hasRole('super-admin') && !auth()->user()->hasRole('super-admin')) {
            abort(403, "Vous ne pouvez pas supprimer un compte Super Admin.");
        }

        $user->delete();
        return redirect()->route('admin.users')->with('success', 'Utilisateur supprimé avec succès.');
    }
}
