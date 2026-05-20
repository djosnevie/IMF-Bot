<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TicketController extends Controller
{
    /**
     * Afficher la liste paginée des tickets avec filtres.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = Ticket::with(['complaint', 'assignedAgent'])
            ->orderBy('created_at', 'desc');

        // Filtrage par scope : si l'utilisateur ne peut pas voir tous les tickets, il ne voit que les siens
        if (!auth()->user()->can('tickets.view_all')) {
            $query->where('assigned_to', auth()->id());
        }

        // Filtre par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtre par priorité
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filtre par agent assigné (le formulaire envoie l'uuid, on résout l'ID)
        if ($request->filled('assigned_to')) {
            $filterAgent = User::where('uuid', $request->assigned_to)->first();
            if ($filterAgent) {
                $query->where('assigned_to', $filterAgent->id);
            }
        }

        $tickets = $query->paginate(15)->withQueryString();
        $agents = User::orderBy('name')->get();

        return view('admin.tickets.index', compact('tickets', 'agents'));
    }

    /**
     * Afficher le détail d'un ticket avec sa plainte, conversation et commentaires.
     *
     * @param Ticket $ticket
     * @return \Illuminate\View\View
     */
    public function show(Ticket $ticket)
    {
        $ticket->load([
            'complaint.conversation.messages' => function ($query) {
                $query->orderBy('created_at', 'asc');
            },
            'comments.author',
            'assignedAgent',
        ]);

        $agents = User::orderBy('name')->get();

        return view('admin.tickets.show', compact('ticket', 'agents'));
    }

    /**
     * Assigner un agent à un ticket.
     *
     * @param Request $request
     * @param Ticket  $ticket
     * @return \Illuminate\Http\RedirectResponse
     */
    public function assign(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'assigned_to' => 'required|exists:users,uuid',
        ]);

        $agent = User::where('uuid', $data['assigned_to'])->firstOrFail();
        $ticket->update(['assigned_to' => $agent->id]);

        Log::info('🎫 Ticket assigné', [
            'ticket' => $ticket->reference,
            'agent' => $agent->name,
        ]);

        return redirect()->back()->with('success', "Ticket assigné à {$agent->name} avec succès.");
    }

    /**
     * Ajouter un commentaire sur un ticket.
     *
     * Si le commentaire n'est pas interne, il est envoyé au client via WhatsApp.
     *
     * @param Request $request
     * @param Ticket  $ticket
     * @return \Illuminate\Http\RedirectResponse
     */
    public function comment(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'content' => 'required|string|max:2000',
            'is_internal' => 'boolean',
        ]);

        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'content' => $data['content'],
            'is_internal' => $data['is_internal'] ?? false,
        ]);

        // Si le commentaire n'est pas interne, envoyer au client via WhatsApp
        if (!$comment->is_internal) {
            try {
                $ticket->load('complaint.conversation');
                $userIdentifier = $ticket->complaint->conversation->user_identifier;

                $message = "📩 Réponse concernant votre plainte *{$ticket->reference}* :\n\n{$data['content']}";

                $webhookService = app(WebhookService::class);
                $webhookService->sendWhatsAppMessage($userIdentifier, $message);

                Log::info('💬 Commentaire envoyé au client via WhatsApp', [
                    'ticket' => $ticket->reference,
                    'user' => $userIdentifier,
                ]);
            } catch (\Exception $e) {
                Log::error('Erreur envoi commentaire WhatsApp : ' . $e->getMessage());
            }
        }

        return redirect()->back()->with('success', 'Commentaire ajouté avec succès.');
    }

    /**
     * Mettre à jour le statut d'un ticket.
     *
     * Envoie une notification WhatsApp au client si le ticket est résolu ou fermé.
     *
     * @param Request $request
     * @param Ticket  $ticket
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStatus(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'status' => 'required|in:new,in_progress,pending,resolved,closed',
        ]);

        $updateData = ['status' => $data['status']];

        // Si résolu, enregistrer la date de résolution
        if ($data['status'] === 'resolved') {
            $updateData['resolved_at'] = now();
        }

        $ticket->update($updateData);

        // Notifier le client si le ticket est résolu ou fermé
        if (in_array($data['status'], ['resolved', 'closed'])) {
            try {
                $ticket->load('complaint.conversation');
                $userIdentifier = $ticket->complaint->conversation->user_identifier;

                $statusLabel = $data['status'] === 'resolved' ? 'résolu' : 'fermé';
                $message = "✅ Votre plainte *{$ticket->reference}* a été {$statusLabel}.\n\nMerci pour votre patience. N'hésitez pas à nous contacter si vous avez d'autres préoccupations.";

                $webhookService = app(WebhookService::class);
                $webhookService->sendWhatsAppMessage($userIdentifier, $message);

                Log::info("🎫 Ticket {$statusLabel}, client notifié", [
                    'ticket' => $ticket->reference,
                    'user' => $userIdentifier,
                ]);
            } catch (\Exception $e) {
                Log::error('Erreur notification statut WhatsApp : ' . $e->getMessage());
            }
        }

        $statusLabels = [
            'new' => 'Nouveau',
            'in_progress' => 'En cours',
            'pending' => 'En attente',
            'resolved' => 'Résolu',
            'closed' => 'Fermé',
        ];

        return redirect()->back()->with('success', "Statut mis à jour : {$statusLabels[$data['status']]}.");
    }
}
