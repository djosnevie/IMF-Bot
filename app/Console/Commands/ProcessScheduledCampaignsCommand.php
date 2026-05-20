<?php

namespace App\Console\Commands;

use App\Services\CampaignService;
use Illuminate\Console\Command;

class ProcessScheduledCampaignsCommand extends Command
{
    protected $signature   = 'crm:campaigns:process';
    protected $description = 'Envoie les campagnes CRM dont l\'heure planifiée est passée.';

    /**
     * Traite et envoie toutes les campagnes planifiées arrivées à échéance.
     * Appelée par le Scheduler toutes les heures.
     *
     * @param CampaignService $campaignService Service de campagnes injecté
     *
     * @return int Code de sortie (0 = succès)
     */
    public function handle(CampaignService $campaignService): int
    {
        $this->info('[CRM] Traitement des campagnes planifiées...');
        $count = $campaignService->processScheduledCampaigns();
        $this->info("[CRM] {$count} campagne(s) traitée(s).");
        return Command::SUCCESS;
    }
}
