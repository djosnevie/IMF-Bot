<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\AgentAlert;

class AlertController extends Controller
{
    /**
     * Affiche les alertes non lues de l'agent connecté.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $alerts = AgentAlert::with('contact')
            ->where('agent_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.crm.alerts.index', compact('alerts'));
    }

    /**
     * Marque une alerte comme lue.
     *
     * @param AgentAlert $alert Alerte à marquer comme lue
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markRead(AgentAlert $alert)
    {
        // Sécurité : un agent ne peut marquer que ses propres alertes
        if ($alert->agent_id !== auth()->id()) {
            abort(403);
        }

        $alert->update(['read_at' => now()]);

        return back()->with('success', 'Alerte marquée comme lue.');
    }

    /**
     * Marque toutes les alertes non lues de l'agent comme lues.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAllRead()
    {
        AgentAlert::where('agent_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'Toutes les alertes ont été marquées comme lues.');
    }

    /**
     * Retourne le nombre d'alertes non lues pour le badge AJAX de la sidebar.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function unreadCount()
    {
        $count = AgentAlert::where('agent_id', auth()->id())
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }
}
