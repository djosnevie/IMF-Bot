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
            $messageId = $parsedData['message_id'] ?? null;

            // 🚫 Déduplication pour éviter les réponses multiples lors des retentatives de WhatsApp
            if ($messageId) {
                if (\Illuminate\Support\Facades\Cache::has('processed_message_' . $messageId)) {
                    \Log::info('⏭️ Message ignoré (déjà en cours de traitement / déjà traité)', ['message_id' => $messageId]);
                    return response()->json(['status' => 'ok'], 200);
                }
                \Illuminate\Support\Facades\Cache::put('processed_message_' . $messageId, true, now()->addMinutes(5));
            }

            // 🔍 LOG: Type de message
            \Log::info('💬 MESSAGE DÉTECTÉ', [
                'user' => $userIdentifier,
                'type' => $messageType,
                'content' => $content
            ]);

            $isText = $messageType === 'text' && !empty($content);
            $isInteractive = $messageType === 'interactive';

            // For text and interactive messages, process with ChatbotService
            if ($isText || $isInteractive) {
                \Log::info('🤖 Traitement avec ChatbotService', ['content' => $content, 'type' => $messageType]);

                $result = $this->chatbotService->processMessage(
                    $userIdentifier,
                    $content,
                    'whatsapp',
                    $parsedData
                );

                if ($result['success']) {
                    $aiResponse = $result['response'];

                    // Simuler la frappe humaine avant d'envoyer la réponse
                    if (!empty($aiResponse)) {
                        $this->webhookService->simulateTyping($parsedData['message_id'], $aiResponse);
                    }

                    if (!empty($result['send_as_cta_url'])) {
                        $token = $result['form_token'];
                        $this->webhookService->sendCtaUrlMessage(
                            $userIdentifier, 
                            "Appuyez ci-dessous pour déposer votre réclamation.\n\nLien valable 10 min • Formulaire sécurisé", 
                            "Ma réclamation", 
                            env('APP_URL') . '/wa/c/' . $token,
                            "Réclamation",
                            "IMF Bisou Bisou"
                        );
                    } elseif (!empty($aiResponse)) {
                        // Send response via WhatsApp as plain text
                        $this->webhookService->sendWhatsAppMessage(
                            $userIdentifier,
                            $aiResponse
                        );
                    }

                    $sent = true;

                    $this->webhookService->logWebhook(
                        'whatsapp',
                        $payload,
                        ['ai_response_sent' => $sent],
                        'success',
                        null,
                        $ipAddress
                    );

                    return response()->json(['status' => 'ok'], 200);
                } else {
                    $errorResponse = $result['response'];
                    $this->webhookService->simulateTyping($parsedData['message_id'], $errorResponse);
                    $this->webhookService->sendWhatsAppMessage($userIdentifier, trim($errorResponse));
                    
                    $this->webhookService->logWebhook(
                        'whatsapp',
                        $payload,
                        null,
                        'failed',
                        $result['error'] ?? 'Unknown error',
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
