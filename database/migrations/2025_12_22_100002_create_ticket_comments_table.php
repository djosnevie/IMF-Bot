<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Créer la table des commentaires sur les tickets.
     */
    public function up(): void
    {
        Schema::create('ticket_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->boolean('is_internal')->default(false); // true = note interne non envoyée au client
            $table->timestamps();

            $table->index('ticket_id');
        });
    }

    /**
     * Supprimer la table des commentaires.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_comments');
    }
};
