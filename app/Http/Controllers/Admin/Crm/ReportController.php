<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\Campaign;
use App\Models\Crm\Contact;
use App\Models\Crm\ContactTag;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Affiche le tableau de bord des rapports CRM avec 4 blocs de métriques.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // 1. Nouveaux contacts par semaine (8 dernières semaines)
        $newContactsPerWeek = Contact::select(
                DB::raw('YEARWEEK(created_at, 1) as week'),
                DB::raw('COUNT(*) as total')
            )
            ->where('created_at', '>=', now()->subWeeks(8))
            ->groupBy('week')
            ->orderBy('week')
            ->get()
            ->map(fn($r) => ['week' => $r->week, 'total' => $r->total]);

        // 2. Taux de conversion par statut
        $conversionByStatus = Contact::select(
                'commercial_status',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('commercial_status')
            ->orderBy('total', 'desc')
            ->get();

        // 3. Produits les plus consultés (top tags produit)
        $topProducts = ContactTag::select('name', DB::raw('COUNT(*) as total'))
            ->where('name', 'like', 'produit:%')
            ->groupBy('name')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn($t) => [
                'name'  => str_replace('produit:', '', $t->name),
                'total' => $t->total,
            ]);

        // 4. Performance des campagnes (5 dernières)
        $campaigns = Campaign::where('status', 'sent')
            ->withCount(['logs as total_sent', 'logs as total_replied' => fn($q) => $q->whereNotNull('replied_at')])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn($c) => [
                'name'          => $c->name,
                'sent'          => $c->total_sent,
                'replied'       => $c->total_replied,
                'response_rate' => $c->total_sent > 0
                    ? round(($c->total_replied / $c->total_sent) * 100, 1)
                    : 0,
            ]);

        // Métriques globales
        $totals = [
            'contacts'   => Contact::count(),
            'leads'      => Contact::where('commercial_status', 'lead')->count(),
            'clients'    => Contact::where('commercial_status', 'client')->count(),
            'avg_score'  => (int) Contact::avg('interest_score'),
        ];

        return view('admin.crm.reports.index', compact(
            'newContactsPerWeek',
            'conversionByStatus',
            'topProducts',
            'campaigns',
            'totals'
        ));
    }

    /**
     * Exporte le rapport des contacts en CSV.
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportCsv()
    {
        $contacts = Contact::with(['agent', 'tags'])->get();

        return response()->streamDownload(function () use ($contacts) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['WhatsApp', 'Nom', 'Statut', 'Score', 'Dernier contact', 'Agent', 'Tags']);
            foreach ($contacts as $c) {
                fputcsv($handle, [
                    $c->whatsapp_number,
                    $c->display_name ?? '—',
                    $c->commercial_status,
                    $c->interest_score,
                    $c->last_contact_at?->format('d/m/Y') ?? '—',
                    $c->agent?->name ?? '—',
                    $c->tags->pluck('name')->implode(', '),
                ]);
            }
            fclose($handle);
        }, 'rapport_crm_' . now()->format('Ymd') . '.csv');
    }
}
