<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('user_identifier'); // Phone number or user ID
            $table->string('platform')->default('whatsapp'); // whatsapp, messenger, web, etc.
            $table->enum('status', ['active', 'closed', 'archived'])->default('active');
            $table->json('metadata')->nullable(); // Additional data (user name, language, etc.)
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            
            $table->index('user_identifier');
            $table->index('platform');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
