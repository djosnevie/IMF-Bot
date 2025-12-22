<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotService
{
    /**
     * Process incoming message from user
     */
    public function processMessage(string $userIdentifier, string $messageContent, string $platform = 'whatsapp')
    {
        try {
            // Get or create conversation
            $conversation = Conversation::getOrCreate($userIdentifier, $platform);

            // Save user message
            $userMessage = $this->saveMessage($conversation->id, 'user', $messageContent);

            // Generate AI response
            $aiResponse = $this->generateAIResponse($conversation, $messageContent);

            // Save bot message
            $botMessage = $this->saveMessage(
                $conversation->id,
                'bot',
                $aiResponse['content'],
                $aiResponse['metadata']
            );

            // Update conversation last message time
            $conversation->update(['last_message_at' => now()]);

            return [
                'success' => true,
                'response' => $aiResponse['content'],
                'conversation_id' => $conversation->id,
            ];
        } catch (\Exception $e) {
            Log::error('Chatbot processing error: ' . $e->getMessage());
            return [
                'success' => false,
                'response' => "Désolée, je rencontre un problème technique. Veuillez réessayer dans un moment.",
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate AI response using OpenAI or Gemini
     */
    protected function generateAIResponse(Conversation $conversation, string $userMessage)
    {
        $provider = config('chatbot.ai_provider', 'openai');

        // Get conversation history for context
        $conversationHistory = $this->getConversationHistory($conversation);

        if ($provider === 'openai') {
            return $this->generateOpenAIResponse($conversationHistory, $userMessage);
        } elseif ($provider === 'gemini') {
            return $this->generateGeminiResponse($conversationHistory, $userMessage);
        }

        throw new \Exception("AI provider not configured");
    }

    /**
     * Generate response using OpenAI
     */
    protected function generateOpenAIResponse(array $conversationHistory, string $userMessage)
    {
        $apiKey = config('chatbot.openai_api_key');
        $model = config('chatbot.openai_model', 'gpt-4');

        $messages = [
            [
                'role' => 'system',
                'content' => $this->getSystemPrompt()
            ]
        ];

        // Add conversation history
        foreach ($conversationHistory as $msg) {
            $messages[] = [
                'role' => $msg->sender_type === 'user' ? 'user' : 'assistant',
                'content' => $msg->content
            ];
        }

        // Add current message
        $messages[] = [
            'role' => 'user',
            'content' => $userMessage
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'max_tokens' => 500,
                ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'content' => $data['choices'][0]['message']['content'],
                'metadata' => [
                    'provider' => 'openai',
                    'model' => $model,
                    'tokens_used' => $data['usage']['total_tokens'] ?? null,
                ]
            ];
        }

        throw new \Exception('OpenAI API error: ' . $response->body());
    }

    /**
     * Generate response using Google Gemini
     */
    protected function generateGeminiResponse(array $conversationHistory, string $userMessage)
    {
        $apiKey = config('chatbot.gemini_api_key');
        $model = config('chatbot.gemini_model', 'gemini-pro');

        // Build conversation context
        $context = $this->getSystemPrompt() . "\n\n";
        foreach ($conversationHistory as $msg) {
            $context .= ($msg->sender_type === 'user' ? 'Utilisateur: ' : 'Madame Sophie: ') . $msg->content . "\n";
        }
        $context .= "Utilisateur: " . $userMessage . "\nMadame Sophie: ";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->timeout(30)->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $context]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 500,
                    ]
                ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'content' => $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Désolée, je n\'ai pas pu générer de réponse.',
                'metadata' => [
                    'provider' => 'gemini',
                    'model' => $model,
                ]
            ];
        }

        throw new \Exception('Gemini API error: ' . $response->body());
    }

    /**
     * Get conversation history (last 10 messages)
     */
    protected function getConversationHistory(Conversation $conversation): array
    {
        return $conversation->messages()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->reverse()
            ->values()
            ->toArray();
    }

    /**
     * Save a message
     */
    protected function saveMessage(int $conversationId, string $senderType, string $content, ?array $metadata = null)
    {
        return Message::create([
            'conversation_id' => $conversationId,
            'sender_type' => $senderType,
            'content' => $content,
            'ai_response_metadata' => $metadata,
        ]);
    }

    /**
     * Get system prompt for Madame Sophie
     */
    protected function getSystemPrompt(): string
    {
        return config(
            'chatbot.system_prompt',
            "Tu es Madame Sophie, une assistante virtuelle bienveillante et professionnelle pour Bisou Bisou, " .
            "une institution de microfinance. Tu aides les clients avec des informations sur les prêts, " .
            "l'épargne, les conditions d'éligibilité, et les procédures. Tu es toujours polie, claire et concise. " .
            "Tu réponds en français et tu es là pour faciliter l'accès aux services financiers."
        );
    }
}
