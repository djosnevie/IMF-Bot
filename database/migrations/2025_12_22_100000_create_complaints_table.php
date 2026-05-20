<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Créer la table des plaintes clients.
     */
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('whatsapp_number');
            $table->string('subject');
            $table->text('description');
            $table->enum('category', ['credit', 'account', 'service', 'other'])->default('other');
            $table->enum('status', ['pending', 'open', 'resolved', 'closed'])->default('pending');
            $table->timestamps();

            $table->index('whatsapp_number');
            $table->index('status');
            $table->index('category');
        });
    }

    /**
     * Supprimer la table des plaintes.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
