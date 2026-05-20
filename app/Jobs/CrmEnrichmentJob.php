<?php

namespace App\Jobs;

use App\Models\Crm\Contact;
use App\Services\CrmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job d'enrichissement CRM déclenché après chaque message WhatsApp traité.
 * S'exécute en file d'attente 'default', sans jamais bloquer la conversation.
 * En cas d'échec, l'exception est loggée et jamais propagée vers le webhook.
 */
class CrmEnrichmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Nombre maximum de tentatives en cas d'échec.
     */
    public int $tries = 3;

    /**
     * Délai en secondes entre les tentatives.
     */
    public int $backoff = 30;

    /**
     * Crée une nouvelle instance du job.
     *
     * @param int    $contactId Identifiant du contact CRM
     * @param string $userMsg   Message envoyé par le client
     * @param string $aiResp    Réponse générée par Sophie (IA)
     */
    public function __construct(
        private readonly int    $contactId,
        private readonly string $userMsg,
        private readonly string $aiResp,
    ) {}

    /**
     * Exécute la chaîne d'enrichissement CRM dans cet ordre strict :
     * 1. Enrichissement des tags et détection d'intentions
     * 2. Recalcul du score
     * 3. Proposition de progression de stage
     * 4. Génération des alertes agents
     *
     * @param CrmService $crmService Service CRM injecté par le container
     *
     * @return void
     */
    public function handle(CrmService $crmService): void
    {
        try {
            $contact = Contact::findOrFail($this->contactId);

            // 1. Enrichissement depuis la réponse IA
            $crmService->enrichFromAiResponse($contact, $this->userMsg, $this->aiResp);

            // 2. Recalcul du score (fraîcheur des données après enrichissement)
            $contact->refresh();
            $crmService->updateScore($contact);

            // 3. Progression automatique du pipeline si critères atteints
            $contact->refresh();
            $crmService->suggestPipelineProgression($contact);

            // 4. Génération des alertes pour les agents assignés
            $contact->refresh();
            $crmService->generateAlerts($contact);

        } catch (\Exception $e) {
            // L'exception est loggée silencieusement — jamais propagée vers le webhook
            Log::error('[CrmEnrichmentJob] Erreur silencieuse', [
                'contact_id' => $this->contactId,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
        }
    }
}
