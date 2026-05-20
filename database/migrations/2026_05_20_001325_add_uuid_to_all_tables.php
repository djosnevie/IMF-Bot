<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Ajouter une colonne uuid à toutes les tables concernées.
     * On conserve les IDs auto-incrément pour les FK internes.
     * Seules les URLs admin exposent l'uuid.
     */
    public function up(): void
    {
        $tables = [
            'users',
            'conversations',
            'tickets',
            'complaints',
            'accounts',
            'credits',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->uuid('uuid')->nullable()->unique()->after('id');
            });

            // Générer les UUID pour les lignes existantes
            DB::table($table)->whereNull('uuid')->orderBy('id')->each(function ($row) use ($table) {
                DB::table($table)->where('id', $row->id)->update(['uuid' => (string) Str::uuid()]);
            });

            // Rendre non-nullable maintenant que toutes les lignes ont un UUID
            Schema::table($table, function (Blueprint $t) {
                $t->uuid('uuid')->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'users',
            'conversations',
            'tickets',
            'complaints',
            'accounts',
            'credits',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('uuid');
            });
        }
    }
};
