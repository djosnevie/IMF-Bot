<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\ChatbotController;

use App\Models\Account;
use App\Models\Credit;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

// Authentication Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// WhatsApp Webhook Routes (Public)
Route::get('/webhook', [WebhookController::class, 'verify']);
Route::post('/webhook', [WebhookController::class, 'handle']);

// Admin Dashboard Routes (Protected)
Route::prefix('admin')->middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/conversations', [DashboardController::class, 'conversations'])->name('admin.conversations');
    Route::get('/conversations/{id}', [DashboardController::class, 'showConversation'])->name('admin.conversations.show');
    Route::get('/comptes', [DashboardController::class, 'accounts'])->name('admin.accounts');
    Route::get('/comptes/creer', [DashboardController::class, 'createAccount'])->name('admin.accounts.create');
    Route::post('/comptes', [DashboardController::class, 'storeAccount'])->name('admin.accounts.store');
    Route::get('/comptes/{id}/modifier', [DashboardController::class, 'editAccount'])->name('admin.accounts.edit');
    Route::put('/comptes/{id}', [DashboardController::class, 'updateAccount'])->name('admin.accounts.update');
    Route::delete('/comptes/{id}', [DashboardController::class, 'destroyAccount'])->name('admin.accounts.destroy');
    Route::get('/credits', [DashboardController::class, 'credits'])->name('admin.credits');
    Route::get('/credits/creer', [DashboardController::class, 'createCredit'])->name('admin.credits.create');
    Route::post('/credits', [DashboardController::class, 'storeCredit'])->name('admin.credits.store');
    Route::get('/credits/{id}/modifier', [DashboardController::class, 'editCredit'])->name('admin.credits.edit');
    Route::put('/credits/{id}', [DashboardController::class, 'updateCredit'])->name('admin.credits.update');
    Route::delete('/credits/{id}', [DashboardController::class, 'destroyCredit'])->name('admin.credits.destroy');
    Route::get('/logs', [DashboardController::class, 'logs'])->name('admin.logs');
    Route::get('/utilisateurs', [DashboardController::class, 'users'])->name('admin.users');
    Route::get('/utilisateurs/creer', [DashboardController::class, 'createUser'])->name('admin.users.create');
    Route::post('/utilisateurs', [DashboardController::class, 'storeUser'])->name('admin.users.store');
    Route::delete('/utilisateurs/{id}', [DashboardController::class, 'destroyUser'])->name('admin.users.destroy');
});
