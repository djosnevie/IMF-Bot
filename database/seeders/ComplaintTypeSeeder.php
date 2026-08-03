<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ComplaintTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['code' => 'report', 'name' => 'REPORT'],
            ['code' => 'manquant_compte', 'name' => 'MANQUANT SUR LE COMPTE'],
            ['code' => 'plainte_likelemba', 'name' => 'PLAINTE LIKELEMBA'],
            ['code' => 'plainte_credit', 'name' => 'PLAINTE CREDIT'],
            ['code' => 'demande', 'name' => 'DEMANDE'],
            ['code' => 'reclamation_caution', 'name' => 'RECLAMATION DE CAUTION'],
            ['code' => 'ecart_solde', 'name' => 'ECART DE SOLDE'],
            ['code' => 'plainte_generique', 'name' => 'PLAINTE'],
        ];

        foreach ($types as $type) {
            \App\Models\ComplaintType::updateOrCreate(
                ['code' => $type['code']],
                ['name' => $type['name'], 'is_active' => true]
            );
        }
    }
}
