<?php

namespace App\Services;

use App\Models\WebhookLog;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Parse WhatsApp webhook payload
     */
    public function parseWhatsAppWebhook(array $payload): ?array
    {
        try {
            // WhatsApp Business API webhook structure
            if (!isset($payload['entry'][0]['changes'][0]['value'])) {
                return null;
            }

            $value = $payload['entry'][0]['changes'][0]['value'];

            // Check if it's a message
            if (!isset($value['messages'][0])) {
                return null;
            }

            $message = $value['messages'][0];
            $contact = $value['contacts'][0] ?? [];
            $messageType = $message['type'] ?? 'text';

            $parsedData = [
                'user_identifier' => $message['from'] ?? null,
                'message_id' => $message['id'] ?? null,
                'message_type' => $messageType,
                'timestamp' => $message['timestamp'] ?? null,
                'contact_name' => $contact['profile']['name'] ?? null,
            ];

            // Parse based on message type
            if ($messageType === 'text') {
                $parsedData['content'] = $message['text']['body'] ?? '';
            } elseif ($messageType === 'interactive') {
                // Handle button/list responses
                $interactive = $message['interactive'];
                $parsedData['interactive_type'] = $interactive['type'] ?? null;

                if ($interactive['type'] === 'button_reply') {
                    $parsedData['content'] = $interactive['button_reply']['title'] ?? '';
                    $parsedData['button_id'] = $interactive['button_reply']['id'] ?? null;
                } elseif ($interactive['type'] === 'list_reply') {
                    $parsedData['content'] = $interactive['list_reply']['title'] ?? '';
                    $parsedData['list_id'] = $interactive['list_reply']['id'] ?? null;
                } elseif ($interactive['type'] === 'nfm_reply') {
                    $parsedData['content'] = $interactive['nfm_reply']['name'] ?? '';
                    $parsedData['flow_data'] = json_decode($interactive['nfm_reply']['response_json'] ?? '{}', true);
                }
            } else {
                $parsedData['content'] = '';
            }

            return $parsedData;
        } catch (\Exception $e) {
            Log::error('WhatsApp webhook parsing error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Validate WhatsApp webhook signature
     */
    public function validateWhatsAppSignature(string $signature, string $payload): bool
    {
        $appSecret = config('chatbot.whatsapp_app_secret');

        if (!$appSecret) {
            return true; // Skip validation if no secret configured
        }

        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $appSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Send WhatsApp message
     */
    public function sendWhatsAppMessage(string $to, string $message): bool
    {
        try {
            $accessToken = config('chatbot.whatsapp_access_token');
            $phoneNumberId = config('chatbot.whatsapp_phone_number_id');

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post("https://graph.facebook.com/v18.0/{$phoneNumberId}/messages", [
                        'messaging_product' => 'whatsapp',
                        'to' => $to,
                        'type' => 'text',
                        'text' => [
                            'body' => $message
                        ]
                    ]);

            if (!$response->successful()) {
                Log::error('❌ WhatsApp send message API error: ' . $response->body());
            } else {
                Log::info('✅ WhatsApp message sent successfully to ' . $to);
            }

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('WhatsApp send message exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send typing indicator (typing_on)
     */
    public function sendTypingIndicator(string $messageId): bool
    {
        try {
            $accessToken = config('chatbot.whatsapp_access_token');
            $phoneNumberId = config('chatbot.whatsapp_phone_number_id');

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post("https://graph.facebook.com/v18.0/{$phoneNumberId}/messages", [
                        'messaging_product' => 'whatsapp',
                        'status' => 'read',
                        'message_id' => $messageId,
                        'typing_indicator' => [
                            'type' => 'text'
                        ]
                    ]);

            if (!$response->successful()) {
                Log::error('❌ WhatsApp typing indicator API error: ' . $response->body());
            }

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('WhatsApp typing indicator exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Simuler un délai de frappe humain avant l'envoi d'une réponse.
     *
     * Active l'indicateur "en train d'écrire" puis attend un délai proportionnel
     * à la longueur du message pour donner l'impression qu'un humain rédige la réponse.
     * L'indicateur reste actif pendant 25 secondes ou jusqu'à l'envoi du message.
     *
     * @param string $messageId L'ID du message entrant auquel on répond
     * @param string $message   Le message qui sera envoyé (pour calculer le délai)
     *
     * @return void
     */
    public function simulateTyping(string $messageId, string $message): void
    {
        // Calculer le délai en fonction de la longueur du message
        $length = mb_strlen($message);
        $delay = $this->calculateTypingDelay($length);

        // Envoyer l'indicateur de frappe (qui marque aussi le message comme lu)
        $this->sendTypingIndicator($messageId);

        Log::info('⌨️ Simulation de frappe', [
            'message_id' => $messageId,
            'message_length' => $length,
            'delay_seconds' => $delay,
        ]);

        // Attendre le délai calculé (l'indicateur reste actif côté client)
        sleep($delay);
    }

    /**
     * Calculer le délai de frappe en secondes selon la longueur du message.
     *
     * @param int $length Nombre de caractères du message
     *
     * @return int Délai en secondes (entre 1 et 5)
     */
    private function calculateTypingDelay(int $length): int
    {
        if ($length < 20) {
            return 0;  // Quasi-instantané
        }

        if ($length < 100) {
            return 1;  // Réponse courte
        }

        return 2;      // Réponse longue (limité à 2s pour éviter le timeout du webhook WhatsApp)
    }

    /**
     * Send interactive buttons
     */
    public function sendButtons(string $to, string $bodyText, array $buttons, ?string $headerText = null, ?string $footerText = null): bool
    {
        try {
            $accessToken = config('chatbot.whatsapp_access_token');
            $phoneNumberId = config('chatbot.whatsapp_phone_number_id');

            $interactive = [
                'type' => 'button',
                'body' => [
                    'text' => $bodyText
                ],
                'action' => [
                    'buttons' => $buttons
                ]
            ];

            // Header optionnel
            if ($headerText) {
                $interactive['header'] = [
                    'type' => 'text',
                    'text' => $headerText
                ];
            }

            // Footer optionnel
            if ($footerText) {
                $interactive['footer'] = [
                    'text' => $footerText
                ];
            }

            $data = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'interactive',
                'interactive' => $interactive
            ];

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post("https://graph.facebook.com/v18.0/{$phoneNumberId}/messages", $data);

            if ($response->successful()) {
                Log::info('✅ Boutons envoyés avec succès', ['to' => $to]);
                return true;
            }

            Log::error('❌ Erreur envoi boutons', ['response' => $response->json()]);
            return false;

        } catch (\Exception $e) {
            Log::error('WhatsApp send buttons error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send interactive list message
     */
    public function sendListMessage(string $to, string $bodyText, string $buttonText, array $sections, ?string $headerText = null, ?string $footerText = null): bool
    {
        try {
            $accessToken = config('chatbot.whatsapp_access_token');
            $phoneNumberId = config('chatbot.whatsapp_phone_number_id');

            $interactive = [
                'type' => 'list',
                'body' => [
                    'text' => $bodyText
                ],
                'action' => [
                    'button' => $buttonText,
                    'sections' => $sections
                ]
            ];

            // Header optionnel
            if ($headerText) {
                $interactive['header'] = [
                    'type' => 'text',
                    'text' => $headerText
                ];
            }

            // Footer optionnel
            if ($footerText) {
                $interactive['footer'] = [
                    'text' => $footerText
                ];
            }

            $data = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'interactive',
                'interactive' => $interactive
            ];

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post("https://graph.facebook.com/v18.0/{$phoneNumberId}/messages", $data);

            if ($response->successful()) {
                Log::info('✅ Liste envoyée avec succès', ['to' => $to]);
                return true;
            }

            Log::error('❌ Erreur envoi liste', ['response' => $response->json()]);
            return false;

        } catch (\Exception $e) {
            Log::error('WhatsApp send list error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send WhatsApp Flow Message
     */
    public function sendFlowMessage(string $to, string $bodyText, string $flowToken, string $ctaText = 'Ouvrir'): bool
    {
        try {
            $accessToken = config('chatbot.whatsapp_access_token');
            $phoneNumberId = config('chatbot.whatsapp_phone_number_id');
            $flowId = env('WHATSAPP_COMPLAINT_FLOW_ID', 'COMPLAINT_FORM'); // Fallback to what user provided

            $flowIdentifierKey = is_numeric($flowId) ? 'flow_id' : 'flow_name';

            $interactive = [
                'type' => 'flow',
                'body' => [
                    'text' => $bodyText
                ],
                'action' => [
                    'name' => 'flow',
                    'parameters' => [
                        'flow_message_version' => '3',
                        'flow_token' => $flowToken,
                        $flowIdentifierKey => $flowId,
                        'mode' => env('WHATSAPP_FLOW_MODE', 'draft'), // Permet de tester les brouillons
                        'flow_cta' => $ctaText
                    ]
                ]
            ];

            $data = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'interactive',
                'interactive' => $interactive
            ];

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post("https://graph.facebook.com/v18.0/{$phoneNumberId}/messages", $data);

            if ($response->successful()) {
                \Log::info('✅ Flow envoyé avec succès', ['to' => $to]);
                return true;
            }

            \Log::error('❌ Erreur envoi Flow', ['response' => $response->json()]);
            return false;

        } catch (\Exception $e) {
            \Log::error('WhatsApp send flow error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send WhatsApp CTA URL (Interactive message)
     */
    public function sendCtaUrlMessage(string $to, string $bodyText, string $buttonText, string $url, ?string $headerText = null, ?string $footerText = null): bool
    {
        try {
            $accessToken = config('chatbot.whatsapp_access_token');
            $phoneNumberId = config('chatbot.whatsapp_phone_number_id');

            $interactive = [
                'type' => 'cta_url',
                'body' => [
                    'text' => $bodyText
                ],
                'action' => [
                    'name' => 'cta_url',
                    'parameters' => [
                        'display_text' => $buttonText,
                        'url' => $url,
                    ]
                ]
            ];

            if ($headerText) {
                $interactive['header'] = [
                    'type' => 'text',
                    'text' => $headerText
                ];
            }

            if ($footerText) {
                $interactive['footer'] = [
                    'text' => $footerText
                ];
            }

            $data = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'interactive',
                'interactive' => $interactive
            ];

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post("https://graph.facebook.com/v18.0/{$phoneNumberId}/messages", $data);

            if ($response->successful()) {
                Log::info('✅ CTA URL envoyé avec succès', ['to' => $to]);
                return true;
            }

            Log::error('❌ Erreur envoi CTA URL', ['response' => $response->json()]);
            return false;

        } catch (\Exception $e) {
            Log::error('WhatsApp send cta url error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Log webhook call
     */
    public function logWebhook(string $platform, array $payload, ?array $response = null, string $status = 'success', ?string $errorMessage = null, ?string $ipAddress = null)
    {
        return WebhookLog::create([
            'platform' => $platform,
            'payload' => $payload,
            'response' => $response,
            'status' => $status,
            'error_message' => $errorMessage,
            'ip_address' => $ipAddress,
        ]);
    }
}
