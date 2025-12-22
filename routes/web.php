<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\ChatbotController;

use App\Models\Account;
use App\Models\Credit;

use App\Http\Controllers\Admin\DashboardController;

Route::get('/', function () {
    $accounts = Account::orderBy('display_order')->get();
    $credits = Credit::orderBy('display_order')->get();
    return view('welcome', compact('accounts', 'credits'));
});

// WhatsApp Webhook Routes
Route::get('/webhook', [WebhookController::class, 'verify']);
Route::post('/webhook', [WebhookController::class, 'handle']);

// Admin Dashboard Routes
Route::prefix('admin')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/conversations', [DashboardController::class, 'conversations'])->name('admin.conversations');
    Route::get('/conversations/{id}', [DashboardController::class, 'showConversation'])->name('admin.conversations.show');
    Route::get('/comptes', [DashboardController::class, 'accounts'])->name('admin.accounts');
    Route::get('/credits', [DashboardController::class, 'credits'])->name('admin.credits');
    Route::get('/logs', [DashboardController::class, 'logs'])->name('admin.logs');
});
