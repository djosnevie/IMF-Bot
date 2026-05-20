<?php

namespace Database\Seeders;

use App\Models\Crm\PipelineStage;
use Illuminate\Database\Seeder;

class PipelineStagesSeeder extends Seeder
{
    /**
     * Pré-remplit les quatre étapes par défaut du pipeline de conversion CRM.
     */
    public function run(): void
    {
        $stages = [
            ['label' => 'Lead',     'color' => '#6B7280', 'sort_order' => 1, 'is_final' => false],
            ['label' => 'Prospect', 'color' => '#3B82F6', 'sort_order' => 2, 'is_final' => false],
            ['label' => 'En cours', 'color' => '#F59E0B', 'sort_order' => 3, 'is_final' => false],
            ['label' => 'Client',   'color' => '#10B981', 'sort_order' => 4, 'is_final' => true],
        ];

        foreach ($stages as $stage) {
            PipelineStage::firstOrCreate(['label' => $stage['label']], $stage);
        }
    }
}
