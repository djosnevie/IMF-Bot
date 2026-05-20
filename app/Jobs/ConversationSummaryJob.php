<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Services\CrmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job déclenché 30 minutes après la dernière activité d'une conversation.
 * Génère un résumé IA structuré et le stocke dans la fiche du contact.
 * File d'attente : low.
 */
class ConversationSummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $backoff = 60;

    /**
     * @param int $conversationId Identifiant de la conversation à résumer
     */
    public function __construct(private readonly int $conversationId) {}

    /**
     * Génère le résumé IA de la conversation et le persiste dans le contact CRM.
     *
     * @param CrmService $crmService Service CRM injecté
     *
     * @return void
     */
    public function handle(CrmService $crmService): void
    {
        try {
            $conversation = Conversation::with('messages')->findOrFail($this->conversationId);
            $crmService->generateConversationSummary($conversation);
        } catch (\Exception $e) {
            Log::error('[ConversationSummaryJob] Erreur silencieuse', [
                'conversation_id' => $this->conversationId,
                'error'           => $e->getMessage(),
            ]);
        }
    }
}
