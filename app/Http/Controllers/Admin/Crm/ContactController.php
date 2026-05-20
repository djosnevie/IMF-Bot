<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\Contact;
use App\Models\Crm\ContactTag;
use App\Models\Crm\PipelineStage;
use App\Models\User;
use App\Services\CrmService;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function __construct(private readonly CrmService $crmService) {}

    /**
     * Affiche la liste paginée des contacts avec filtres.
     * Un agent ne voit que ses contacts assignés (scope).
     *
     * @param Request $request Requête HTTP avec filtres optionnels
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = Contact::with(['agent', 'tags'])->orderBy('last_contact_at', 'desc');

        // Scope : un agent ne voit que ses propres contacts assignés
        if (! auth()->user()->hasAnyRole(['super-admin', 'admin', 'supervisor'])) {
            $query->where('assigned_to', auth()->id());
        }

        // Filtre par statut commercial
        if ($request->filled('status')) {
            $query->where('commercial_status', $request->status);
        }

        // Filtre par tag
        if ($request->filled('tag')) {
            $query->whereHas('tags', fn($q) => $q->where('name', $request->tag));
        }

        // Filtre par plage de score
        if ($request->filled('min_score')) {
            $query->where('interest_score', '>=', (int) $request->min_score);
        }
        if ($request->filled('max_score')) {
            $query->where('interest_score', '<=', (int) $request->max_score);
        }

        // Filtre par agent assigné
        if ($request->filled('agent_id')) {
            $query->where('assigned_to', $request->agent_id);
        }

        $contacts      = $query->paginate(20)->withQueryString();
        $agents        = User::orderBy('name')->get();
        $popularTags   = ContactTag::select('name')->groupBy('name')->orderByRaw('COUNT(*) DESC')->limit(20)->pluck('name');
        $stages        = PipelineStage::orderBy('sort_order')->get();

        return view('admin.crm.contacts.index', compact('contacts', 'agents', 'popularTags', 'stages'));
    }

    /**
     * Affiche la fiche 360° d'un contact avec timeline, tags, score et résumé IA.
     *
     * @param Contact $contact Contact à afficher (résolu via UUID)
     *
     * @return \Illuminate\View\View
     */
    public function show(Contact $contact)
    {
        // Scope agent
        if (! auth()->user()->hasAnyRole(['super-admin', 'admin', 'supervisor'])) {
            if ($contact->assigned_to !== auth()->id()) {
                abort(403, 'Vous n\'êtes pas assigné à ce contact.');
            }
        }

        $contact->load(['tags', 'agent', 'pipelineHistory.toStage', 'scoreHistory']);
        $timeline  = $contact->timeline();
        $stages    = PipelineStage::orderBy('sort_order')->get();
        $agents    = User::orderBy('name')->get();

        return view('admin.crm.contacts.show', compact('contact', 'timeline', 'stages', 'agents'));
    }

    /**
     * Met à jour manuellement le statut commercial (stage pipeline) d'un contact.
     *
     * @param Request $request Requête avec le champ 'status'
     * @param Contact $contact Contact à mettre à jour
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStage(Request $request, Contact $contact)
    {
        $data = $request->validate([
            'commercial_status' => 'required|in:lead,prospect,en_cours,client,inactif',
        ]);

        $ancien = $contact->commercial_status;
        $contact->update($data);

        // Traçabilité dans le pipeline
        $fromStage = PipelineStage::where('label', ucfirst(str_replace('_', ' ', $ancien)))->first();
        $toStage   = PipelineStage::where('label', ucfirst(str_replace('_', ' ', $data['commercial_status'])))->first();

        if ($toStage) {
            \App\Models\Crm\ContactPipelineHistory::create([
                'contact_id'    => $contact->id,
                'from_stage_id' => $fromStage?->id,
                'to_stage_id'   => $toStage->id,
                'changed_by'    => auth()->id(),
                'reason'        => 'mise_a_jour_manuelle_agent',
            ]);
        }

        return back()->with('success', 'Statut mis à jour avec succès.');
    }

    /**
     * Assigne un agent responsable à un contact.
     *
     * @param Request $request Requête avec le champ 'agent_id'
     * @param Contact $contact Contact à mettre à jour
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function assignAgent(Request $request, Contact $contact)
    {
        $data = $request->validate([
            'agent_id' => 'required|exists:users,id',
        ]);

        $contact->update(['assigned_to' => $data['agent_id']]);

        return back()->with('success', 'Agent assigné avec succès.');
    }

    /**
     * Ajoute une note manuelle au contact (stockée dans metadata).
     *
     * @param Request $request Requête avec le champ 'note'
     * @param Contact $contact Contact cible
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function addNote(Request $request, Contact $contact)
    {
        $data = $request->validate([
            'note' => 'required|string|max:1000',
        ]);

        $metadata          = $contact->metadata ?? [];
        $metadata['notes'] = $metadata['notes'] ?? [];
        $metadata['notes'][] = [
            'content'   => $data['note'],
            'author'    => auth()->user()->name,
            'author_id' => auth()->id(),
            'date'      => now()->toIso8601String(),
        ];

        $contact->update(['metadata' => $metadata]);

        return back()->with('success', 'Note ajoutée avec succès.');
    }

    /**
     * Exporte la liste des contacts filtrés au format CSV.
     *
     * @param Request $request Requête avec filtres actifs
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportCsv(Request $request)
    {
        $query = Contact::with(['agent', 'tags'])->orderBy('last_contact_at', 'desc');

        if (! auth()->user()->hasAnyRole(['super-admin', 'admin', 'supervisor'])) {
            $query->where('assigned_to', auth()->id());
        }
        if ($request->filled('status')) {
            $query->where('commercial_status', $request->status);
        }

        $contacts = $query->get();

        return response()->streamDownload(function () use ($contacts) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Numéro WhatsApp', 'Nom', 'Statut', 'Score', 'Dernier contact', 'Agent', 'Tags']);
            foreach ($contacts as $c) {
                fputcsv($handle, [
                    $c->whatsapp_number,
                    $c->display_name ?? '—',
                    $c->commercial_status,
                    $c->interest_score,
                    $c->last_contact_at?->format('d/m/Y H:i') ?? '—',
                    $c->agent?->name ?? '—',
                    $c->tags->pluck('name')->implode(', '),
                ]);
            }
            fclose($handle);
        }, 'contacts_crm_' . now()->format('Ymd') . '.csv');
    }
}
