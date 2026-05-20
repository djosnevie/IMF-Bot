<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\CampaignLog;
use App\Models\Crm\Contact;
use App\Models\Crm\ContactTag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    /**
     * Affiche tous les tags existants avec leur nombre d'occurrences.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $tags = ContactTag::select('name', 'source')
            ->selectRaw('COUNT(*) as occurrences')
            ->groupBy('name', 'source')
            ->orderByDesc('occurrences')
            ->paginate(30);

        return view('admin.crm.tags.index', compact('tags'));
    }

    /**
     * Crée un tag manuel sur un contact.
     *
     * @param Request $request Requête avec 'contact_uuid' et 'name'
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'contact_uuid' => 'required|exists:contacts,uuid',
            'name'         => 'required|string|max:100|regex:/^[a-z0-9\-\:\_]+$/',
        ]);

        $contact = Contact::where('uuid', $data['contact_uuid'])->firstOrFail();

        ContactTag::firstOrCreate(
            ['contact_id' => $contact->id, 'name' => $data['name']],
            ['source' => 'manual']
        );

        return back()->with('success', "Tag « {$data['name']} » ajouté avec succès.");
    }

    /**
     * Fusionne deux tags en un seul pour nettoyer les doublons générés par l'IA.
     * Le tag source est renommé en tag cible sur tous les contacts concernés.
     *
     * @param Request $request Requête avec 'source_tag' et 'target_tag'
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function merge(Request $request)
    {
        $data = $request->validate([
            'source_tag' => 'required|string|max:100',
            'target_tag' => 'required|string|max:100|different:source_tag',
        ]);

        // Renommer le tag source → target en ignorant les doublons
        $toUpdate = ContactTag::where('name', $data['source_tag'])->get();
        $merged   = 0;

        foreach ($toUpdate as $tag) {
            $exists = ContactTag::where('contact_id', $tag->contact_id)
                ->where('name', $data['target_tag'])
                ->exists();

            if ($exists) {
                $tag->delete();
            } else {
                $tag->update(['name' => $data['target_tag']]);
            }
            $merged++;
        }

        return back()->with('success', "{$merged} occurrence(s) du tag « {$data['source_tag']} » fusionnée(s) vers « {$data['target_tag']} ».");
    }
}
