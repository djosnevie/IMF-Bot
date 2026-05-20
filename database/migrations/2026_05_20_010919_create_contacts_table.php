<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('whatsapp_number')->unique();
            $table->string('display_name')->nullable();
            $table->string('detected_language', 10)->nullable()->default('fr');
            $table->enum('commercial_status', ['lead', 'prospect', 'en_cours', 'client', 'inactif'])->default('lead');
            $table->unsignedTinyInteger('interest_score')->default(0); // 0-100
            $table->timestamp('first_contact_at')->nullable();
            $table->timestamp('last_contact_at')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable(); // résumé IA, zone géo, canal, etc.
            $table->timestamps();

            $table->index('commercial_status');
            $table->index('interest_score');
            $table->index('last_contact_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
