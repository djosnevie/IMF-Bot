<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\Conversation;
use App\Models\Ticket;
use Illuminate\Support\Facades\Log;

class ComplaintService
{
    /**
     * Créer une plainte et son ticket associé à partir d'une conversation.
     *
     * @param Conversation $conversation  La conversation WhatsApp en cours
     * @param string       $subject       Le sujet de la plainte
     * @param string       $description   La description détaillée de la plainte
     * @param string       $category      La catégorie détectée (credit, account, service, other)
     *
     * @return Ticket Le ticket créé et associé à la plainte
     */
    public function createFromConversation(
        Conversation $conversation,
        string $subject,
        string $description,
        string $category
    ): Ticket {
        // Créer la plainte
        $complaint = Complaint::create([
            'conversation_id' => $conversation->id,
            'whatsapp_number' => $conversation->user_identifier,
            'subject' => $subject,
            'description' => $description,
            'category' => $category,
            'status' => 'pending',
        ]);

        // Générer la référence du ticket (TKT-YYYY-XXXX)
        $reference = $this->generateTicketReference();

        // Créer le ticket associé
        $ticket = Ticket::create([
            'complaint_id' => $complaint->id,
            'reference' => $reference,
            'priority' => 'medium',
            'status' => 'new',
        ]);

        Log::info('📋 Plainte et ticket créés', [
            'complaint_id' => $complaint->id,
            'ticket_reference' => $reference,
            'whatsapp_number' => $conversation->user_identifier,
            'category' => $category,
        ]);

        return $ticket;
    }

    /**
     * Détecter la catégorie d'une plainte à partir du sujet et de la description.
     *
     * Analyse les mots-clés présents dans le texte pour deviner la catégorie appropriée.
     *
     * @param string $subject     Le sujet de la plainte
     * @param string $description La description de la plainte
     *
     * @return string La catégorie détectée parmi : credit, account, service, other
     */
    public function detectCategory(string $subject, string $description): string
    {
        $text = mb_strtolower($subject . ' ' . $description);

        // Mots-clés liés aux crédits
        $creditKeywords = ['crédit', 'credit', 'prêt', 'pret', 'remboursement', 'emprunt', 'échéance', 'echeance', 'décaissement', 'decaissement'];
        foreach ($creditKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return 'credit';
            }
        }

        // Mots-clés liés aux comptes
        $accountKeywords = ['compte', 'épargne', 'epargne', 'virement', 'solde', 'retrait', 'dépôt', 'depot', 'relevé', 'releve', 'salaire'];
        foreach ($accountKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return 'account';
            }
        }

        // Mots-clés liés aux services
        $serviceKeywords = ['service', 'agence', 'accueil', 'attente', 'personnel', 'horaire', 'application', 'transfert'];
        foreach ($serviceKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return 'service';
            }
        }

        return 'other';
    }

    /**
     * Générer une référence unique de ticket au format TKT-YYYY-XXXX.
     *
     * @return string La référence générée
     */
    private function generateTicketReference(): string
    {
        $year = now()->year;
        $count = Ticket::whereYear('created_at', $year)->count();
        $nextNumber = str_pad($count + 1, 4, '0', STR_PAD_LEFT);

        return "TKT-{$year}-{$nextNumber}";
    }
}
