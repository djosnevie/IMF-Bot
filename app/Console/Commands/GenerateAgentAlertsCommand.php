<?php

namespace App\Console\Commands;

use App\Models\Crm\AgentAlert;
use App\Models\Crm\Contact;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateAgentAlertsCommand extends Command
{
    protected $signature   = 'crm:alerts:generate';
    protected $description = 'Génère les alertes d\'inactivité et de score pour les agents assignés.';

    /**
     * Passe en revue tous les contacts actifs et génère des alertes
     * d'inactivité et de score pour les agents assignés.
     * Appelée chaque matin à 8h00.
     *
     * @return int Code de sortie (0 = succès)
     */
    public function handle(): int
    {
        $this->info('[CRM] Génération des alertes agents...');
        $created = 0;

        Contact::whereNotNull('assigned_to')
            ->where('commercial_status', '!=', 'inactif')
            ->chunk(100, function ($contacts) use (&$created) {
                foreach ($contacts as $contact) {
                    try {
                        // Alerte inactivité > 14 jours
                        if ($contact->last_contact_at) {
                            $jours = now()->diffInDays($contact->last_contact_at);
                            if ($jours > 14) {
                                $dejaAlerte = AgentAlert::where('contact_id', $contact->id)
                                    ->where('type', 'client_inactif')
                                    ->where('created_at', '>', now()->subDays(14))
                                    ->exists();

                                if (! $dejaAlerte) {
                                    AgentAlert::create([
                                        'type'       => 'client_inactif',
                                        'contact_id' => $contact->id,
                                        'agent_id'   => $contact->assigned_to,
                                        'message'    => "Le contact {$contact->whatsapp_number} est inactif depuis {$jours} jours.",
                                    ]);
                                    $created++;
                                }
                            }
                        }

                        // Alerte score élevé (>= 75)
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
                                    'message'    => "Score élevé ({$contact->interest_score}/100) pour {$contact->whatsapp_number}.",
                                ]);
                                $created++;
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error('[GenerateAgentAlertsCommand] Erreur', [
                            'contact_id' => $contact->id,
                            'error'      => $e->getMessage(),
                        ]);
                    }
                }
            });

        $this->info("[CRM] {$created} alerte(s) créée(s).");
        return Command::SUCCESS;
    }
}
