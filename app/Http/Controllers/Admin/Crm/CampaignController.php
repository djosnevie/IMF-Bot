<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\Campaign;
use App\Models\Crm\ContactTag;
use App\Services\CampaignService;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function __construct(private readonly CampaignService $campaignService) {}

    /**
     * Affiche la liste des campagnes avec leurs statistiques.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $campaigns = Campaign::withCount('logs')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.crm.campaigns.index', compact('campaigns'));
    }

    /**
     * Affiche le formulaire de création d'une campagne.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $availableTags = ContactTag::select('name')
            ->groupBy('name')
            ->orderBy('name')
            ->pluck('name');

        return view('admin.crm.campaigns.create', compact('availableTags'));
    }

    /**
     * Enregistre une nouvelle campagne (statut draft ou scheduled).
     *
     * @param Request $request Données du formulaire
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                    => 'required|string|max:255',
            'message_template'        => 'required|string|max:4096',
            'scheduled_at'            => 'nullable|date|after:now',
            'criteria_tags'           => 'nullable|array',
            'criteria_tags.*'         => 'string',
            'criteria_min_score'      => 'nullable|integer|min:0|max:100',
            'criteria_inactivity_days' => 'nullable|integer|min:1',
            'criteria_statuses'       => 'nullable|array',
            'criteria_statuses.*'     => 'in:lead,prospect,en_cours,client,inactif',
        ]);

        $campaign = Campaign::create([
            'name'             => $data['name'],
            'message_template' => $data['message_template'],
            'scheduled_at'     => $data['scheduled_at'] ?? null,
            'status'           => $data['scheduled_at'] ? 'scheduled' : 'draft',
            'created_by'       => auth()->id(),
            'targeting_criteria' => [
                'tags'               => $data['criteria_tags'] ?? [],
                'min_score'          => $data['criteria_min_score'] ?? null,
                'max_inactivity_days' => $data['criteria_inactivity_days'] ?? null,
                'statuses'           => $data['criteria_statuses'] ?? [],
            ],
        ]);

        return redirect()->route('admin.crm.campaigns.index')
            ->with('success', "Campagne « {$campaign->name} » créée avec succès.");
    }

    /**
     * Retourne le nombre de contacts éligibles selon les critères (appel AJAX).
     *
     * @param Request $request Critères de ciblage
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function eligibleCount(Request $request)
    {
        $criteria = [
            'tags'               => $request->input('criteria_tags', []),
            'min_score'          => $request->input('criteria_min_score'),
            'max_inactivity_days' => $request->input('criteria_inactivity_days'),
            'statuses'           => $request->input('criteria_statuses', []),
        ];

        $count = $this->campaignService->getEligibleContacts($criteria)->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Affiche le formulaire d'édition d'une campagne non envoyée.
     *
     * @param Campaign $campaign Campagne à éditer
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit(Campaign $campaign)
    {
        if (! in_array($campaign->status, ['draft', 'scheduled'])) {
            return redirect()->route('admin.crm.campaigns.index')
                ->with('error', 'Seules les campagnes non envoyées (brouillon ou planifiées) peuvent être modifiées.');
        }

        $availableTags = ContactTag::select('name')
            ->groupBy('name')
            ->orderBy('name')
            ->pluck('name');

        return view('admin.crm.campaigns.edit', compact('campaign', 'availableTags'));
    }

    /**
     * Met à jour une campagne non envoyée.
     *
     * @param Request  $request  Données du formulaire
     * @param Campaign $campaign Campagne à mettre à jour
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Campaign $campaign)
    {
        if (! in_array($campaign->status, ['draft', 'scheduled'])) {
            return redirect()->route('admin.crm.campaigns.index')
                ->with('error', 'Seules les campagnes non envoyées (brouillon ou planifiées) peuvent être modifiées.');
        }

        $data = $request->validate([
            'name'                    => 'required|string|max:255',
            'message_template'        => 'required|string|max:4096',
            'scheduled_at'            => 'nullable|date|after:now',
            'criteria_tags'           => 'nullable|array',
            'criteria_tags.*'         => 'string',
            'criteria_min_score'      => 'nullable|integer|min:0|max:100',
            'criteria_inactivity_days' => 'nullable|integer|min:1',
            'criteria_statuses'       => 'nullable|array',
            'criteria_statuses.*'     => 'in:lead,prospect,en_cours,client,inactif',
        ]);

        $campaign->update([
            'name'             => $data['name'],
            'message_template' => $data['message_template'],
            'scheduled_at'     => $data['scheduled_at'] ?? null,
            'status'           => $data['scheduled_at'] ? 'scheduled' : 'draft',
            'targeting_criteria' => [
                'tags'               => $data['criteria_tags'] ?? [],
                'min_score'          => $data['criteria_min_score'] ?? null,
                'max_inactivity_days' => $data['criteria_inactivity_days'] ?? null,
                'statuses'           => $data['criteria_statuses'] ?? [],
            ],
        ]);

        return redirect()->route('admin.crm.campaigns.index')
            ->with('success', "Campagne « {$campaign->name} » mise à jour avec succès.");
    }

    /**
     * Annule une campagne planifiée ou en draft.
     *
     * @param Campaign $campaign Campagne à annuler
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancel(Campaign $campaign)
    {
        if (! in_array($campaign->status, ['draft', 'scheduled'])) {
            return back()->with('error', 'Seules les campagnes planifiées ou en brouillon peuvent être annulées.');
        }

        $campaign->update(['status' => 'cancelled']);

        return back()->with('success', "Campagne « {$campaign->name} » annulée.");
    }
}
