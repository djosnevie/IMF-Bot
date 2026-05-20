<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_alerts', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['prospect_chaud', 'client_inactif', 'ticket_en_attente', 'score_eleve']);
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->timestamp('read_at')->nullable(); // null = non lu
            $table->timestamps();

            $table->index(['agent_id', 'read_at']);
            $table->index('contact_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_alerts');
    }
};
