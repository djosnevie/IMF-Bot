<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\ChatbotController;

use App\Models\Account;
use App\Models\Credit;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\TicketController;

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

    // Conversations
    Route::middleware('permission:conversations.view_all|conversations.view_assigned')->group(function () {
        Route::get('/conversations', [DashboardController::class, 'conversations'])->name('admin.conversations');
        Route::get('/conversations/{id}', [DashboardController::class, 'showConversation'])->name('admin.conversations.show');
    });

    // Produits (Comptes & Crédits)
    Route::middleware('permission:products.manage')->group(function () {
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
    });

    // Logs
    Route::middleware('permission:logs.view')->group(function () {
        Route::get('/logs', [DashboardController::class, 'logs'])->name('admin.logs');
    });

    // Utilisateurs
    Route::prefix('utilisateurs')->group(function () {
        Route::middleware('permission:users.view')->get('/', [DashboardController::class, 'users'])->name('admin.users');
        
        Route::middleware('permission:users.manage')->group(function () {
            Route::get('/creer', [DashboardController::class, 'createUser'])->name('admin.users.create');
            Route::post('/', [DashboardController::class, 'storeUser'])->name('admin.users.store');
            Route::get('/{id}/modifier', [DashboardController::class, 'editUser'])->name('admin.users.edit');
            Route::post('/{id}/role', [DashboardController::class, 'assignRole'])->name('admin.users.role');
            Route::post('/{id}/permissions', [DashboardController::class, 'syncPermissions'])->name('admin.users.permissions');
            Route::delete('/{id}', [DashboardController::class, 'destroyUser'])->name('admin.users.destroy');
        });
    });

    // Tickets & Plaintes
    Route::prefix('tickets')->name('admin.tickets.')->middleware('permission:tickets.view_all|tickets.view_assigned')->group(function () {
        Route::get('/', [TicketController::class, 'index'])->name('index');
        Route::get('/{ticket}', [TicketController::class, 'show'])->name('show');
        Route::post('/{ticket}/assign', [TicketController::class, 'assign'])->name('assign')->middleware('permission:tickets.assign');
        Route::post('/{ticket}/comment', [TicketController::class, 'comment'])->name('comment')->middleware('permission:tickets.comment_internal|tickets.comment_public');
        Route::patch('/{ticket}/status', [TicketController::class, 'updateStatus'])->name('updateStatus')->middleware('permission:tickets.assign');
    });
});
