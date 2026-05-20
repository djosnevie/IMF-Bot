<?php

namespace App\Services;

use App\Models\Crm\Campaign;
use App\Models\Crm\CampaignLog;
use App\Models\Crm\Contact;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class CampaignService
{
    public function __construct(private readonly WebhookService $webhookService) {}

    // ─── Étape 7 : Contacts éligibles ─────────────────────────────────────────

    /**
     * Retourne la liste des contacts éligibles à une campagne selon les critères.
     *
     * @param array $criteria {
     *   tags?: string[],               Tags que le contact doit posséder (union)
     *   min_score?: int,               Score minimum requis
     *   max_inactivity_days?: int,     Nombre de jours d'inactivité minimum
     *   statuses?: string[],           Statuts commerciaux ciblés (lead, prospect, etc.)
     * }
     *
     * @return Collection<Contact> Contacts correspondant aux critères
     */
    public function getEligibleContacts(array $criteria): Collection
    {
        $query = Contact::query();

        // Filtre par score minimum
        if (! empty($criteria['min_score'])) {
            $query->where('interest_score', '>=', (int) $criteria['min_score']);
        }

        // Filtre par statut commercial
        if (! empty($criteria['statuses'])) {
            $query->whereIn('commercial_status', $criteria['statuses']);
        }

        // Filtre par inactivité (dernier contact il y a au moins N jours)
        if (! empty($criteria['max_inactivity_days'])) {
            $query->where('last_contact_at', '<=', now()->subDays((int) $criteria['max_inactivity_days']));
        }

        // Filtre par tags (le contact doit avoir AU MOINS UN des tags)
        if (! empty($criteria['tags'])) {
            $query->whereHas('tags', function ($q) use ($criteria) {
                $q->whereIn('name', $criteria['tags']);
            });
        }

        return $query->get();
    }

    // ─── Étape 7 : Envoi d'une campagne ───────────────────────────────────────

    /**
     * Envoie une campagne à tous ses contacts éligibles.
     * Personnalise le template et trace chaque envoi dans campaign_logs.
     *
     * ⚠️ CONTRAINTE META — FENÊTRE DE 24 HEURES :
     * WhatsApp Business API impose que les messages envoyés hors de la fenêtre
     * de 24h suivant le dernier message du client DOIVENT être des templates
     * approuvés dans Meta Business Manager.
     * Si last_contact_at > 24h, cette méthode logue un avertissement.
     * Il est de la responsabilité de l'administrateur de n'utiliser que des
     * templates validés par Meta pour les contacts hors fenêtre.
     * Référence : https://developers.facebook.com/docs/whatsapp/pricing
     *
     * @param Campaign $campaign Campagne à envoyer
     *
     * @return array Statistiques d'envoi {sent: int, skipped: int, errors: int}
     */
    public function sendCampaign(Campaign $campaign): array
    {
        $criteria  = $campaign->targeting_criteria ?? [];
        $contacts  = $this->getEligibleContacts($criteria);
        $sent = $skipped = $errors = 0;

        foreach ($contacts as $contact) {
            try {
                // Vérification contrainte fenêtre 24h Meta
                $hoursInactive = $contact->last_contact_at
                    ? now()->diffInHours($contact->last_contact_at)
                    : 99999;

                if ($hoursInactive > 24) {
                    Log::warning('[CampaignService] Contact hors fenêtre 24h Meta', [
                        'contact'       => $contact->whatsapp_number,
                        'campaign'      => $campaign->name,
                        'hours_inactive' => $hoursInactive,
                        'action'        => 'Vérifier que le template est approuvé par Meta.',
                    ]);
                }

                // Personnalisation du template
                $message = $this->personalizeTemplate($campaign->message_template, $contact);

                // Envoi via WhatsApp Business API
                $this->webhookService->sendWhatsAppMessage($contact->whatsapp_number, $message);

                // Traçabilité
                CampaignLog::create([
                    'campaign_id'  => $campaign->id,
                    'contact_id'   => $contact->id,
                    'message_sent' => $message,
                    'sent_at'      => now(),
                ]);

                $sent++;
            } catch (\Exception $e) {
                Log::error('[CampaignService] Erreur envoi contact', [
                    'contact'   => $contact->whatsapp_number,
                    'campaign'  => $campaign->name,
                    'error'     => $e->getMessage(),
                ]);
                $errors++;
            }
        }

        // Mise à jour des statistiques de la campagne
        $campaign->update([
            'status'           => 'sent',
            'recipients_count' => $sent,
        ]);

        Log::info('[CampaignService] Campagne envoyée', [
            'campaign' => $campaign->name,
            'sent'     => $sent,
            'errors'   => $errors,
        ]);

        return ['sent' => $sent, 'skipped' => $skipped, 'errors' => $errors];
    }

    // ─── Étape 7 : Traitement des campagnes planifiées ────────────────────────

    /**
     * Traite et envoie toutes les campagnes dont l'heure d'envoi planifiée est
     * passée et dont le statut est 'scheduled'.
     * Appelée par ProcessScheduledCampaignsCommand toutes les heures.
     *
     * @return int Nombre de campagnes traitées
     */
    public function processScheduledCampaigns(): int
    {
        $campaigns = Campaign::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($campaigns as $campaign) {
            try {
                $this->sendCampaign($campaign);
            } catch (\Exception $e) {
                Log::error('[CampaignService] Erreur traitement campagne planifiée', [
                    'campaign_id' => $campaign->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        return $campaigns->count();
    }

    // ─── Helpers privés ───────────────────────────────────────────────────────

    /**
     * Remplace les variables du template par les données du contact.
     *
     * Variables disponibles : {nom}, {produit}, {score}, {statut}
     *
     * @param string  $template Template de message avec variables entre accolades
     * @param Contact $contact  Contact cible
     *
     * @return string Message personnalisé prêt à l'envoi
     */
    private function personalizeTemplate(string $template, Contact $contact): string
    {
        $produitTag = $contact->tags()
            ->where('name', 'like', 'produit:%')
            ->orderBy('created_at', 'desc')
            ->first();

        $produit = $produitTag
            ? str_replace('produit:', '', $produitTag->name)
            : 'nos produits';

        $replacements = [
            '{nom}'    => $contact->display_name ?? $contact->whatsapp_number,
            '{produit}' => $produit,
            '{score}'  => (string) $contact->interest_score,
            '{statut}' => $contact->commercial_status,
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
    }
}
