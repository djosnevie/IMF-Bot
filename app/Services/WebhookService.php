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

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('WhatsApp send message error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send typing indicator (typing_on)
     */
    public function sendTypingIndicator(string $to): bool
    {
        try {
            $accessToken = config('chatbot.whatsapp_access_token');
            $phoneNumberId = config('chatbot.whatsapp_phone_number_id');

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post("https://graph.facebook.com/v18.0/{$phoneNumberId}/messages", [
                        'messaging_product' => 'whatsapp',
                        'recipient_type' => 'individual',
                        'to' => $to,
                        'sender_action' => 'typing_on'
                    ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('WhatsApp typing indicator error: ' . $e->getMessage());
            return false;
        }
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
