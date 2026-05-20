<?php

namespace App\Console\Commands;

use App\Models\Crm\Contact;
use App\Models\Crm\ContactScoreHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScoreDecayCommand extends Command
{
    protected $signature   = 'crm:score:decay';
    protected $description = 'Applique la pénalité d\'inactivité sur tous les contacts inactifs depuis plus de 30 jours.';

    /**
     * Applique la pénalité d'inactivité (-5pts/30j) à tous les contacts
     * dont last_contact_at dépasse 30 jours. Appelée chaque nuit à 2h00.
     *
     * @return int Code de sortie (0 = succès)
     */
    public function handle(): int
    {
        $this->info('[CRM] Application des pénalités d\'inactivité...');
        $updated = 0;

        Contact::where('last_contact_at', '<', now()->subDays(30))
            ->where('interest_score', '>', 0)
            ->chunk(100, function ($contacts) use (&$updated) {
                foreach ($contacts as $contact) {
                    try {
                        $joursSansContact = now()->diffInDays($contact->last_contact_at);
                        $tranches         = (int) floor(($joursSansContact - 30) / 30);
                        $penalite         = min($tranches * 5, $contact->interest_score);

                        if ($penalite > 0) {
                            $nouveauScore = max(0, $contact->interest_score - 5); // -5 pts par passage
                            $contact->update(['interest_score' => $nouveauScore]);

                            ContactScoreHistory::create([
                                'contact_id' => $contact->id,
                                'score'      => $nouveauScore,
                                'delta'      => -5,
                                'reason'     => 'penalite_inactivite_nuit',
                            ]);

                            $updated++;
                        }
                    } catch (\Exception $e) {
                        Log::error('[ScoreDecayCommand] Erreur contact', [
                            'contact_id' => $contact->id,
                            'error'      => $e->getMessage(),
                        ]);
                    }
                }
            });

        $this->info("[CRM] {$updated} contact(s) mis à jour.");
        return Command::SUCCESS;
    }
}
