<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    protected $webhookService;
    protected $chatbotService;
    protected $welcomeService;

    public function __construct()
    {
        $this->webhookService = new \App\Services\WebhookService();
        $this->chatbotService = new \App\Services\ChatbotService();
        $this->welcomeService = new \App\Services\WelcomeService();
    }

    /**
     * Verify webhook (GET request from WhatsApp)
     * This is called when you set up the webhook in Meta Developer Console
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $verifyToken = config('chatbot.whatsapp_verify_token');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response()->json(['error' => 'Verification failed'], 403);
    }

    /**
     * Handle incoming webhook (POST request from WhatsApp)
     */
    public function handle(Request $request)
    {
        $payload = $request->all();
        $signature = $request->header('X-Hub-Signature-256', '');
        $ipAddress = $request->ip();

        // 🔍 LOG: Webhook reçu
        \Log::info('📨 WEBHOOK REÇU', [
            'ip' => $ipAddress,
            'payload' => $payload
        ]);

        try {
            // Validate signature
            if (!$this->webhookService->validateWhatsAppSignature($signature, $request->getContent())) {
                \Log::warning('⚠️ Signature invalide', ['ip' => $ipAddress]);
                $this->webhookService->logWebhook(
                    'whatsapp',
                    $payload,
                    null,
                    'failed',
                    'Invalid signature',
                    $ipAddress
                );
                return response()->json(['error' => 'Invalid signature'], 403);
            }

            // Parse webhook
            $parsedData = $this->webhookService->parseWhatsAppWebhook($payload);

            // 🔍 LOG: Données parsées
            \Log::info('📋 DONNÉES PARSÉES', [
                'parsed_data' => $parsedData
            ]);

            if (!$parsedData || !$parsedData['user_identifier']) {
                \Log::info('⏭️ Message ignoré (pas de user_identifier)');
                // Not a message we need to process (could be status update, etc.)
                return response()->json(['status' => 'ok'], 200);
            }

            $userIdentifier = $parsedData['user_identifier'];
            $messageType = $parsedData['message_type'];
            $content = $parsedData['content'] ?? '';

            // 🔍 LOG: Type de message
            \Log::info('💬 MESSAGE DÉTECTÉ', [
                'user' => $userIdentifier,
                'type' => $messageType,
                'content' => $content
            ]);

            // Check if it's a menu request (strict: "Menu")
            if ($messageType === 'text' && $this->welcomeService->isMenuRequest($content)) {
                \Log::info('🏠 Envoi du menu interactif');
                $sent = $this->welcomeService->sendWelcomeMessage($userIdentifier);

                $this->webhookService->logWebhook(
                    'whatsapp',
                    $payload,
                    ['menu_sent' => $sent],
                    'success',
                    null,
                    $ipAddress
                );

                return response()->json(['status' => 'ok'], 200);
            }

            // Check if it's a button click or list selection
            if ($messageType === 'interactive' && (isset($parsedData['button_id']) || isset($parsedData['list_id']))) {
                $interactionId = $parsedData['button_id'] ?? $parsedData['list_id'];
                $interactionType = isset($parsedData['button_id']) ? 'bouton' : 'liste';

                \Log::info("🔘 Interaction $interactionType", ['id' => $interactionId]);

                // Handle interaction (sends message with button/list)
                $sent = $this->welcomeService->handleButtonClick($interactionId, $userIdentifier);

                $this->webhookService->logWebhook(
                    'whatsapp',
                    $payload,
                    ['response_sent' => $sent, 'interaction_id' => $interactionId],
                    'success',
                    null,
                    $ipAddress
                );

                return response()->json(['status' => 'ok'], 200);
            }

            // For other text messages, process with AI (Priority 1 for intelligence)
            if ($messageType === 'text' && !empty($content)) {
                \Log::info('🤖 Traitement avec IA', ['content' => $content]);

                $result = $this->chatbotService->processMessage(
                    $userIdentifier,
                    $content,
                    'whatsapp'
                );

                if ($result['success']) {
                    $aiResponse = $result['response'];
                    $shouldTriggerMenu = str_contains($aiResponse, '[TRIGGER_MENU]');

                    // Clean response from trigger keyword
                    $cleanResponse = str_replace('[TRIGGER_MENU]', '', $aiResponse);

                    // Send response via WhatsApp with a menu button
                    $sent = $this->welcomeService->sendResponseWithMenuButton(
                        $userIdentifier,
                        trim($cleanResponse)
                    );

                    // If AI detected a menu intent, send the interactive menu immediately after
                    if ($shouldTriggerMenu) {
                        \Log::info('📋 Déclenchement automatique du menu par l\'IA');
                        $this->welcomeService->sendWelcomeMessage($userIdentifier);
                    }

                    $this->webhookService->logWebhook(
                        'whatsapp',
                        $payload,
                        ['ai_response_sent' => $sent, 'menu_triggered' => $shouldTriggerMenu],
                        'success',
                        null,
                        $ipAddress
                    );

                    return response()->json(['status' => 'ok'], 200);
                }
            }



            return response()->json(['status' => 'ok'], 200);

        } catch (\Exception $e) {
            // Log exception
            $this->webhookService->logWebhook(
                'whatsapp',
                $payload,
                null,
                'failed',
                $e->getMessage(),
                $ipAddress
            );

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}
