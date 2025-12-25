<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;

class WelcomeService
{
    protected $webhookService;

    public function __construct()
    {
        $this->webhookService = new WebhookService();
    }

    /**
     * Send initial greeting message (text only)
     */
    public function sendGreetingMessage(string $userIdentifier): bool
    {
        $bodyText = "Bonjour 👋🏽, je suis Sophie, assistante virtuelle de l’IMF Bisou Bisou.\n\n" .
            "Comment puis-je vous aider aujourd’hui ?\n\n" .
            "Je peux vous renseigner sur nos comptes, crédits et services.";

        return $this->webhookService->sendWhatsAppMessage($userIdentifier, $bodyText);
    }

    /**
     * Check if message is a greeting request
     */
    public function isGreetingRequest(string $message): bool
    {
        $greetingKeywords = ['bonjour', 'salut', 'hello', 'hi', 'hey', 'accueil', 'start'];
        $messageLower = strtolower(trim($message));

        return in_array($messageLower, $greetingKeywords);
    }
}
