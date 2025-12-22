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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->enum('sender_type', ['user', 'bot']); // Who sent the message
            $table->text('content'); // Message content
            $table->string('message_type')->default('text'); // text, image, document, audio, etc.
            $table->json('ai_response_metadata')->nullable(); // AI model, tokens used, etc.
            $table->string('whatsapp_message_id')->nullable(); // WhatsApp message ID for tracking
            $table->timestamps();

            $table->index('conversation_id');
            $table->index('sender_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
