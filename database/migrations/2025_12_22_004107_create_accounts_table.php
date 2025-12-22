<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();

            /* =========================
             * Identification
             * ========================= */
            $table->string('reference', 50)->unique();
            // Ex: ACC_SAV_IND_USD, ACC_CUR_IND_CDF, ACC_SAL_IND_CDF, ACC_COL_GRP_CDF, ACC_FDT_IND_USD

            $table->string('account_type');
            // Savings Account | Current Account | Salary Account | Collective Account | Fixed Deposit

            $table->string('display_name');
            // Compte Épargne | Compte Courant | Compte Salaire | Compte Collectif | Dépôt à Terme

            /* =========================
             * Segmentation
             * ========================= */
            $table->enum('category', ['individual', 'business', 'group']);
            $table->enum('currency', ['CDF', 'USD']);

            /* =========================
             * Informational conditions
             * (chatbot / public info)
             * ========================= */
            $table->string('interest_rate')->nullable();
            $table->string('initial_deposit')->nullable();
            $table->string('maintenance_fee')->nullable();
            $table->string('statement_fee')->nullable();

            $table->string('deposit_rule')->nullable();
            $table->string('withdrawal_rule')->nullable();
            $table->string('withdrawal_fee')->nullable();

            $table->string('duration')->nullable();
            // Mainly for Fixed Deposit (e.g. 3, 6, 12 months)

            /* =========================
             * Eligibility
             * ========================= */
            $table->unsignedInteger('min_age')->nullable();
            $table->unsignedInteger('max_age')->nullable();

            /* =========================
             * Display / chatbot control
             * ========================= */
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(1);

            $table->timestamps();

            /* =========================
             * Indexes
             * ========================= */
            $table->index(['account_type', 'currency', 'category']);
            $table->index(['is_active', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
