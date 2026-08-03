<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class NotifyAgentsOfNewTicketJob implements ShouldQueue
{
    use Queueable;

    protected $ticket;

    /**
     * Create a new job instance.
     */
    public function __construct(\App\Models\Ticket $ticket)
    {
        $this->ticket = $ticket;
    }

    /**
     * Execute the job.
     */
    public function handle(\App\Services\WebhookService $webhookService): void
    {
        $ticket = $this->ticket->load(['complaint', 'users']);
        $complaint = $ticket->complaint;
        
        $message = "🚨 *Nouveau ticket assigné :* {$ticket->reference}\n";
        if ($complaint && $complaint->sub_category) {
            $complaintType = \App\Models\ComplaintType::where('code', $complaint->sub_category)->first();
            $typeName = $complaintType ? $complaintType->name : $complaint->sub_category;
            $message .= "*Type :* {$typeName}\n";
        }
        $urgency = $complaint ? $complaint->urgency : 'Moyenne';
        $message .= "*Urgence :* " . ucfirst($urgency) . "\n";
        $message .= "*Lien :* https://admin.imf-bisou.com/admin/tickets/{$ticket->uuid}";

        foreach ($ticket->users as $user) {
            if (!empty($user->whatsapp_number)) {
                $webhookService->sendWhatsAppMessage($user->whatsapp_number, $message);
            }
        }
    }
}
