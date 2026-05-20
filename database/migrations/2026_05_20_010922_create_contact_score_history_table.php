<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_score_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score'); // Valeur absolue du score à ce moment
            $table->tinyInteger('delta');         // Variation par rapport au score précédent
            $table->string('reason');             // Ex: 'nouveau_message', 'tag_produit', 'inactivite'
            $table->timestamps();

            $table->index(['contact_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_score_history');
    }
};
