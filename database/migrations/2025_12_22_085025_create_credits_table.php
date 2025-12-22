<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('credits', function (Blueprint $table) {
            $table->id();

            /* =========================
             * Identification
             * ========================= */
            $table->string('reference', 50)->unique();
            $table->string('name');          // English name
            $table->string('display_name'); // French name (Excel)

            /* =========================
             * Informational credit data
             * ========================= */
            $table->string('amount_range')->nullable();     // Montant Min-Max
            $table->string('duration_range')->nullable();   // Durée Min-Max
            $table->string('file_fee')->nullable();         // Frais d’étude de dossier
            $table->string('disbursement_fee')->nullable(); // Frais de décaissement
            $table->string('interest_rate')->nullable();    // Taux d’intérêt
            $table->string('penalty')->nullable();          // Pénalité
            $table->string('repayment_mode')->nullable();   // Mode de remboursement
            $table->string('guarantee')->nullable();        // Garantie exigée

            /* =========================
             * Chatbot control
             * ========================= */
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(1);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credits');
    }
};
