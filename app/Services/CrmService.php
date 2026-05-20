<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Crm\AgentAlert;
use App\Models\Crm\Contact;
use App\Models\Crm\ContactPipelineHistory;
use App\Models\Crm\ContactScoreHistory;
use App\Models\Crm\ContactTag;
use App\Models\Crm\PipelineStage;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CrmService
{
    // ─── Étape 3 : Création / Récupération du contact ─────────────────────────

    /**
     * Trouve ou crée un contact CRM à partir d'un numéro WhatsApp.
     * Met à jour last_contact_at à chaque appel.
     *
     * @param string $whatsappNumber Numéro WhatsApp de l'utilisateur
     * @param array  $metadata       Métadonnées optionnelles (langue, plateforme, etc.)
     *
     * @return Contact Contact trouvé ou créé
     */
    public function findOrCreateContact(string $whatsappNumber, array $metadata = []): Contact
    {
        $contact = Contact::firstOrCreate(
            ['whatsapp_number' => $whatsappNumber],
            [
                'commercial_status' => 'lead',
                'interest_score'    => 0,
                'first_contact_at'  => now(),
                'last_contact_at'   => now(),
            ]
        );

        // Mise à jour de la date de dernier contact
        $contact->update(['last_contact_at' => now()]);

        // Assignation automatique à un agent disponible si aucun n'est assigné
        if (! $contact->assigned_to) {
            $agent = User::role('agent')->inRandomOrder()->first()
                ?? User::role('admin')->first();
            if ($agent) {
                $contact->update(['assigned_to' => $agent->id]);
            }
        }

        return $contact;
    }

    // ─── Étape 3 : Enrichissement depuis la réponse IA ────────────────────────

    /**
     * Enrichit silencieusement un contact en analysant un échange conversationnel.
     * Extrait les produits mentionnés, la langue et les signaux d'intention.
     *
     * @param Contact $contact  Contact à enrichir
     * @param string  $userMsg  Message envoyé par le client
     * @param string  $aiResp   Réponse générée par l'IA (Sophie)
     *
     * @return void
     */
    public function enrichFromAiResponse(Contact $contact, string $userMsg, string $aiResp): void
    {
        try {
            $combinedText = strtolower($userMsg . ' ' . $aiResp);

            // Détection de la langue (heuristique simple FR/EN/SW)
            if (! $contact->detected_language || $contact->detected_language === 'fr') {
                if (preg_match('/\b(hello|credit|account|balance|money|loan)\b/i', $userMsg)) {
                    $contact->update(['detected_language' => 'en']);
                } elseif (preg_match('/\b(habari|pesa|mkopo|akaunti|benki)\b/i', $userMsg)) {
                    $contact->update(['detected_language' => 'sw']);
                }
            }

            // Détection des produits bancaires mentionnés (depuis la base)
            $accounts = \App\Models\Account::where('is_active', true)->get();
            $credits  = \App\Models\Credit::where('is_active', true)->get();

            foreach ($accounts as $account) {
                $keywords = array_filter([
                    strtolower($account->display_name),
                    strtolower($account->account_type),
                    strtolower($account->reference),
                ]);
                foreach ($keywords as $keyword) {
                    if ($keyword && str_contains($combinedText, $keyword)) {
                        $this->addTag($contact, 'produit:' . $account->reference, 'auto');
                        break;
                    }
                }
            }

            foreach ($credits as $credit) {
                $keywords = array_filter([
                    strtolower($credit->display_name),
                    strtolower($credit->name ?? ''),
                    strtolower($credit->reference),
                ]);
                foreach ($keywords as $keyword) {
                    if ($keyword && str_contains($combinedText, $keyword)) {
                        $this->addTag($contact, 'produit:' . $credit->reference, 'auto');
                        break;
                    }
                }
            }

            // Détection des signaux d'intention
            $intentionKeywords = [
                'intérêt:crédit'    => ['crédit', 'prêt', 'emprunt', 'remboursement', 'taux', 'credit', 'loan'],
                'intérêt:épargne'   => ['épargne', 'dépôt', 'compte', 'placer', 'economiser', 'saving', 'deposit'],
                'intérêt:transfert' => ['transfert', 'envoyer', 'virement', 'mobile money', 'transfer'],
                'plainte'           => ['plainte', 'problème', 'insatisfait', 'réclamation', 'erreur'],
            ];

            foreach ($intentionKeywords as $tag => $words) {
                foreach ($words as $word) {
                    if (str_contains($combinedText, $word)) {
                        $this->addTag($contact, $tag, 'auto');
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('[CrmService] Erreur enrichFromAiResponse', [
                'contact_id' => $contact->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    // ─── Étape 4 : Calcul du score ────────────────────────────────────────────

    /**
     * Recalcule le score d'intérêt du contact selon la formule pondérée à 5 critères.
     * Trace chaque variation dans contact_score_history.
     *
     * @param Contact $contact Contact dont le score doit être recalculé
     *
     * @return int Nouveau score (0-100)
     */
    public function updateScore(Contact $contact): int
    {
        try {
            $ancienScore = $contact->interest_score;

            // 1. Messages envoyés (max 25 pts — 1pt/message)
            $messageCount = $contact->conversations()
                ->withCount(['messages as user_messages_count' => fn($q) => $q->where('sender_type', 'user')])
                ->get()
                ->sum('user_messages_count');
            $ptsMessages = min($messageCount, 25);

            // 2. Produits distincts consultés (max 25 pts — 5pts/produit)
            $produitsCount = $contact->tags()
                ->where('name', 'like', 'produit:%')
                ->distinct('name')
                ->count();
            $ptsProduits = min($produitsCount * 5, 25);

            // 3. Ticket existant (20 pts fixes)
            $aTicket = false;
            foreach ($contact->conversations as $conv) {
                if ($conv->complaint && $conv->complaint->ticket) {
                    $aTicket = true;
                    break;
                }
            }
            $ptsTicket = $aTicket ? 20 : 0;

            // 4. Récence (max 20 pts)
            $joursSansContact = $contact->last_contact_at
                ? now()->diffInDays($contact->last_contact_at)
                : 999;
            $ptsRecence = match (true) {
                $joursSansContact <= 7  => 20,
                $joursSansContact <= 30 => 10,
                default                 => 0,
            };

            // 5. Tags auto (max 10 pts — 1pt/tag unique)
            $tagsAutoCount = $contact->autoTags()->distinct('name')->count();
            $ptsTags = min($tagsAutoCount, 10);

            // Pénalité inactivité : -5pts par tranche de 30j après J+30
            $penalite = 0;
            if ($joursSansContact > 30) {
                $tranches = (int) floor(($joursSansContact - 30) / 30);
                $penalite = $tranches * 5;
            }

            $nouveauScore = max(0, min(100,
                $ptsMessages + $ptsProduits + $ptsTicket + $ptsRecence + $ptsTags - $penalite
            ));

            // Sauvegarder si le score a changé
            if ($nouveauScore !== $ancienScore) {
                $contact->update(['interest_score' => $nouveauScore]);

                ContactScoreHistory::create([
                    'contact_id' => $contact->id,
                    'score'      => $nouveauScore,
                    'delta'      => $nouveauScore - $ancienScore,
                    'reason'     => 'recalcul_apres_interaction',
                ]);
            }

            return $nouveauScore;
        } catch (\Exception $e) {
            Log::error('[CrmService] Erreur updateScore', [
                'contact_id' => $contact->id,
                'error'      => $e->getMessage(),
            ]);
            return $contact->interest_score;
        }
    }

    // ─── Étape 3 : Progression automatique du pipeline ───────────────────────

    /**
     * Analyse les tags et le score du contact pour suggérer une progression
     * automatique de stage dans le pipeline de conversion.
     * Trace le changement dans contact_pipeline_history avec auteur = système.
     *
     * @param Contact $contact Contact à analyser
     *
     * @return void
     */
    public function suggestPipelineProgression(Contact $contact): void
    {
        try {
            // Règle : Lead → Prospect si score >= 60 ET au moins 3 tags produits actifs
            if ($contact->commercial_status === 'lead' && $contact->interest_score >= 60) {
                $produitsCount = $contact->tags()
                    ->where('name', 'like', 'produit:%')
                    ->count();

                if ($produitsCount >= 3) {
                    $stageProspect = PipelineStage::where('label', 'Prospect')->first();
                    $stageLead     = PipelineStage::where('label', 'Lead')->first();

                    if ($stageProspect) {
                        ContactPipelineHistory::create([
                            'contact_id'    => $contact->id,
                            'from_stage_id' => $stageLead?->id,
                            'to_stage_id'   => $stageProspect->id,
                            'changed_by'    => null, // null = système
                            'reason'        => 'auto_scoring: score >= 60 et >= 3 produits consultés',
                        ]);

                        $contact->update(['commercial_status' => 'prospect']);

                        Log::info('[CrmService] Progression pipeline automatique', [
                            'contact'  => $contact->whatsapp_number,
                            'nouveau_status' => 'prospect',
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('[CrmService] Erreur suggestPipelineProgression', [
                'contact_id' => $contact->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    // ─── Étape 3 : Génération des alertes ────────────────────────────────────

    /**
     * Vérifie les seuils configurés et crée des alertes pour les agents
     * concernant ce contact si les conditions sont remplies.
     *
     * @param Contact $contact Contact à analyser
     *
     * @return void
     */
    public function generateAlerts(Contact $contact): void
    {
        try {
            if (! $contact->assigned_to) {
                return;
            }

            // Alerte : score franchissant un seuil haut (>= 75)
            if ($contact->interest_score >= 75) {
                $dejaAlerte = AgentAlert::where('contact_id', $contact->id)
                    ->where('type', 'prospect_chaud')
                    ->whereNull('read_at')
                    ->exists();

                if (! $dejaAlerte) {
                    AgentAlert::create([
                        'type'       => 'prospect_chaud',
                        'contact_id' => $contact->id,
                        'agent_id'   => $contact->assigned_to,
                        'message'    => "Le contact {$contact->whatsapp_number} a atteint un score de {$contact->interest_score}/100. C'est un prospect chaud à contacter rapidement.",
                    ]);
                }
            }

            // Alerte : inactivité > 14 jours
            $joursSansContact = $contact->last_contact_at
                ? now()->diffInDays($contact->last_contact_at)
                : 0;

            if ($joursSansContact > 14 && $contact->commercial_status !== 'inactif') {
                $dejaAlerte = AgentAlert::where('contact_id', $contact->id)
                    ->where('type', 'client_inactif')
                    ->where('created_at', '>', now()->subDays(14))
                    ->exists();

                if (! $dejaAlerte) {
                    AgentAlert::create([
                        'type'       => 'client_inactif',
                        'contact_id' => $contact->id,
                        'agent_id'   => $contact->assigned_to,
                        'message'    => "Le contact {$contact->whatsapp_number} n'a pas interagi depuis {$joursSansContact} jours.",
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('[CrmService] Erreur generateAlerts', [
                'contact_id' => $contact->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    // ─── Étape 5 : Résumé IA de conversation ─────────────────────────────────

    /**
     * Génère un résumé structuré JSON d'une conversation via l'API IA.
     * Stocke le résumé dans le champ metadata du contact.
     * Appelé par ConversationSummaryJob 30 minutes après la dernière activité.
     *
     * @param Conversation $conversation Conversation à résumer
     *
     * @return void
     */
    public function generateConversationSummary(Conversation $conversation): void
    {
        try {
            // Récupérer les 20 derniers messages
            $messages = $conversation->messages()
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get()
                ->reverse()
                ->map(fn($m) => "[{$m->sender_type}] {$m->content}")
                ->implode("\n");

            if (empty($messages)) {
                return;
            }

            $prompt = "Analyse cette conversation entre un client WhatsApp et Sophie, l'assistante IA d'une institution de microfinance (IMF Bisou Bisou).\n\n"
                . "CONVERSATION :\n{$messages}\n\n"
                . "Retourne UNIQUEMENT un objet JSON valide avec exactement ces clés :\n"
                . "{\n"
                . "  \"besoin_principal\": \"description courte du besoin exprimé\",\n"
                . "  \"produits_mentionnes\": [\"produit1\", \"produit2\"],\n"
                . "  \"sentiment\": \"positif|neutre|négatif\",\n"
                . "  \"recommandation\": \"rappeler|envoyer_documentation|aucune_action\"\n"
                . "}\n"
                . "Ne génère aucun texte en dehors du JSON.";

            $apiKey = config('services.openai.key') ?? env('OPENAI_API_KEY');
            $model  = config('services.openai.model', 'gpt-4o-mini');

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type'  => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model'    => $model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature'      => 0.2,
                'max_tokens'       => 300,
                'response_format'  => ['type' => 'json_object'],
            ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content', '{}');
                $summary = json_decode($content, true);

                if (is_array($summary)) {
                    // Trouver le contact associé et stocker le résumé
                    $contact = Contact::where('whatsapp_number', $conversation->user_identifier)->first();
                    if ($contact) {
                        $metadata = $contact->metadata ?? [];
                        $metadata['last_conversation_summary'] = array_merge(
                            $summary,
                            ['conversation_id' => $conversation->id, 'generated_at' => now()->toIso8601String()]
                        );
                        $contact->update(['metadata' => $metadata]);

                        Log::info('[CrmService] Résumé conversation généré', [
                            'contact'        => $contact->whatsapp_number,
                            'recommandation' => $summary['recommandation'] ?? 'N/A',
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('[CrmService] Erreur generateConversationSummary', [
                'conversation_id' => $conversation->id,
                'error'           => $e->getMessage(),
            ]);
        }
    }

    // ─── Helpers privés ───────────────────────────────────────────────────────

    /**
     * Ajoute un tag à un contact de manière idempotente (ignore les doublons).
     *
     * @param Contact $contact Contact cible
     * @param string  $name    Nom du tag
     * @param string  $source  'auto' ou 'manual'
     *
     * @return ContactTag Tag créé ou existant
     */
    private function addTag(Contact $contact, string $name, string $source = 'auto'): ContactTag
    {
        return ContactTag::firstOrCreate(
            ['contact_id' => $contact->id, 'name' => $name],
            ['source' => $source]
        );
    }
}
