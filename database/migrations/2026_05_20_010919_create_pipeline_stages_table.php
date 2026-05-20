<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('color', 20)->default('#6B7280'); // Couleur CSS hex
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->boolean('is_final')->default(false); // true = client converti
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pipeline_stages');
    }
};
