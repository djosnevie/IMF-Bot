<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─── CRM — Tâches planifiées ──────────────────────────────────────────────────

// Campagnes planifiées : traitement toutes les heures
Schedule::command('crm:campaigns:process')->hourly()->withoutOverlapping();

// Dégradation des scores : chaque nuit à 2h00
Schedule::command('crm:score:decay')->dailyAt('02:00')->withoutOverlapping();

// Génération des alertes agents : chaque matin à 8h00
Schedule::command('crm:alerts:generate')->dailyAt('08:00')->withoutOverlapping();

// Résumé de conversations inactives depuis 30+ minutes (toutes les 15 min)
Schedule::call(function () {
    $cutoff = now()->subMinutes(30);
    \App\Models\Conversation::where('last_message_at', '<', $cutoff)
        ->whereDoesntHave('messages', fn($q) => $q->where('created_at', '>', $cutoff))
        ->whereNull('metadata->summary_generated_at')
        ->limit(20)
        ->get()
        ->each(fn($conv) => \App\Jobs\ConversationSummaryJob::dispatch($conv->id)->onQueue('low'));
})->everyFifteenMinutes()->name('crm:conversation-summaries')->withoutOverlapping();

