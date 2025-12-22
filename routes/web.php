<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\ChatbotController;

use App\Models\Account;

Route::get('/', function () {
    $accounts = Account::orderBy('display_order')->get();
    return view('welcome', compact('accounts'));
});

// WhatsApp Webhook Routes
Route::get('/webhook', [WebhookController::class, 'verify']);
Route::post('/webhook', [WebhookController::class, 'handle']);

// Protected Admin Routes (require authentication)
Route::middleware('auth')->group(function () {
    // Chatbot admin routes
    Route::prefix('chatbot')->group(function () {
        Route::get('/conversations', [ChatbotController::class, 'index'])->name('chatbot.conversations');
        Route::get('/conversations/{id}', [ChatbotController::class, 'show'])->name('chatbot.conversations.show');
        Route::get('/stats', [ChatbotController::class, 'stats'])->name('chatbot.stats');
    });
});
