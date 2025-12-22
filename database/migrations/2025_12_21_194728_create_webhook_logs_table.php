<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('platform')->default('whatsapp'); // whatsapp, messenger, etc.
            $table->json('payload'); // Incoming webhook payload
            $table->json('response')->nullable(); // Response sent back
            $table->enum('status', ['success', 'failed'])->default('success');
            $table->text('error_message')->nullable(); // Error details if failed
            $table->string('ip_address')->nullable(); // Request IP for security
            $table->timestamps();

            $table->index('platform');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
