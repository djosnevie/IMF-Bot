<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Jobs\CrmEnrichmentJob;
use App\Services\CrmService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotService
{
    /**
     * Traiter un message entrant d'un utilisateur.
     *
     * Vérifie d'abord si un flux de plainte est en cours, sinon passe par l'IA.
     *
     * @param string $userIdentifier  Numéro WhatsApp de l'utilisateur
     * @param string $messageContent  Contenu du message reçu
     * @param string $platform        Plateforme d'origine (whatsapp par défaut)
     *
     * @return array Résultat du traitement avec clés success, response, conversation_id
     */
    public function processMessage(string $userIdentifier, string $messageContent, string $platform = 'whatsapp')
    {
        try {
            // Get or create conversation
            $conversation = Conversation::getOrCreate($userIdentifier, $platform);

            // Save user message
            $userMessage = $this->saveMessage($conversation->id, 'user', $messageContent);

            // 1. Vérifier si un flux de plainte est en cours
            $complaintFlow = $conversation->metadata['complaint_flow'] ?? null;

            if ($complaintFlow) {
                $botResponse = $this->handleComplaintFlow($conversation, $messageContent, $complaintFlow);

                // Sauvegarder la réponse du bot
                $this->saveMessage($conversation->id, 'bot', $botResponse);

                // Mettre à jour le timestamp
                $conversation->update(['last_message_at' => now()]);

                return [
                    'success' => true,
                    'response' => $botResponse,
                    'conversation_id' => $conversation->id,
                ];
            }

            // 2. Sinon : flux normal → appel IA
            $aiResponse = $this->generateAIResponse($conversation, $messageContent);

            $responseContent = $aiResponse['content'];
            $responseContent = $this->formatForWhatsApp($responseContent);

            // 3. Détecter le marqueur de déclenchement de plainte dans la réponse IA
            if (str_contains($responseContent, '[INITIATE_COMPLAINT_FLOW]')) {
                // Nettoyer le marqueur de la réponse visible
                $responseContent = trim(str_replace('[INITIATE_COMPLAINT_FLOW]', '', $responseContent));

                // Initialiser le flux de plainte dans la metadata
                $metadata = $conversation->metadata ?? [];
                $metadata['complaint_flow'] = [
                    'step' => 'awaiting_subject',
                    'subject' => null,
                    'description' => null,
                ];
                $conversation->update(['metadata' => $metadata]);

                // Ajouter la question sur le sujet après la réponse empathique
                $responseContent .= "\n\nQuel est le sujet de votre plainte ? (Ex : problème de remboursement, erreur sur mon compte, etc.)";
            }

            // Save bot message
            $botMessage = $this->saveMessage(
                $conversation->id,
                'bot',
                $responseContent,
                $aiResponse['metadata']
            );

            // Mise à jour du timestamp de la conversation
            $conversation->update(['last_message_at' => now()]);

            // ─── Enrichissement CRM (non-bloquant) ────────────────────────────
            // Dispatché en job asynchrone pour ne pas allonger le temps de
            // réponse perçu par le client WhatsApp.
            try {
                $crmService = app(CrmService::class);
                $contact = $crmService->findOrCreateContact($userIdentifier);
                CrmEnrichmentJob::dispatch($contact->id, $messageContent, $responseContent)
                    ->onQueue('default');
            } catch (\Exception $e) {
                // Silencieux : une erreur CRM ne doit JAMAIS interrompre la conversation
                Log::warning('[ChatbotService] Erreur dispatch CRM (ignorée)', [
                    'error' => $e->getMessage(),
                ]);
            }
            // ─────────────────────────────────────────────────────────────────

            return [
                'success'         => true,
                'response'        => $responseContent,
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
     * Gérer le flux conversationnel de collecte d'une plainte.
     *
     * Gère les étapes successives : collecte du sujet, puis de la description,
     * puis création du ticket via le ComplaintService.
     *
     * @param Conversation $conversation  La conversation en cours
     * @param string       $userMessage   Le message envoyé par l'utilisateur
     * @param array        $flow          L'état actuel du flux (depuis metadata)
     *
     * @return string La réponse à envoyer à l'utilisateur
     */
    private function handleComplaintFlow(Conversation $conversation, string $userMessage, array $flow): string
    {
        $step = $flow['step'];

        if ($step === 'awaiting_subject') {
            // Stocker le sujet, passer à l'étape suivante
            $metadata = $conversation->metadata;
            $metadata['complaint_flow']['subject'] = $userMessage;
            $metadata['complaint_flow']['step'] = 'awaiting_description';
            $conversation->update(['metadata' => $metadata]);

            return "Merci. Pouvez-vous maintenant décrire votre problème en détail ? Plus vous êtes précis(e), plus nous pourrons vous aider rapidement.";
        }

        if ($step === 'awaiting_description') {
            $subject = $flow['subject'];
            $description = $userMessage;

            try {
                // Créer la plainte et le ticket
                $complaintService = app(ComplaintService::class);
                $ticket = $complaintService->createFromConversation(
                    $conversation,
                    $subject,
                    $description,
                    $complaintService->detectCategory($subject, $description)
                );

                // Nettoyer le flux de la metadata
                $metadata = $conversation->metadata;
                unset($metadata['complaint_flow']);
                $conversation->update(['metadata' => $metadata]);

                return "Votre plainte a bien été enregistrée. 📋\n\nRéférence : *{$ticket->reference}*\n\nNotre équipe va examiner votre demande et vous contactera dans les plus brefs délais. Puis-je vous aider avec autre chose ?";
            } catch (\Exception $e) {
                Log::error('Erreur lors de la création de la plainte : ' . $e->getMessage());

                // Nettoyer le flux en cas d'erreur
                $metadata = $conversation->metadata;
                unset($metadata['complaint_flow']);
                $conversation->update(['metadata' => $metadata]);

                return "Je suis désolée, une erreur est survenue lors de l'enregistrement de votre plainte. Veuillez réessayer ou contacter directement notre agence au 218, Avenue Colonel Ebeya Gombe, Kinshasa-RDC.";
            }
        }

        // Cas inattendu : nettoyer le flux
        $metadata = $conversation->metadata;
        unset($metadata['complaint_flow']);
        $conversation->update(['metadata' => $metadata]);

        return "Je suis désolée, une erreur est survenue. Pouvez-vous reformuler votre demande ?";
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
        } elseif ($provider === 'mistral') {
            return $this->generateMistralResponse($conversationHistory, $userMessage);
        }

        throw new \Exception("AI provider not configured or unsupported provider: {$provider}");
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
                'role' => $msg['sender_type'] === 'user' ? 'user' : 'assistant',
                'content' => $msg['content']
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
            $context .= ($msg['sender_type'] === 'user' ? 'Utilisateur: ' : 'Madame Sophie: ') . $msg['content'] . "\n";
        }
        $context .= "Utilisateur: " . $userMessage . "\nMadame Sophie: ";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->withOptions([
            'version' => 1.1,
        ])->timeout(60)->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
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
     * Generate response using Mistral AI
     */
    protected function generateMistralResponse(array $conversationHistory, string $userMessage)
    {
        $apiKey = config('chatbot.mistral_api_key');
        $model = config('chatbot.mistral_model', 'mistral-large-latest');

        $messages = [
            [
                'role' => 'system',
                'content' => $this->getSystemPrompt()
            ]
        ];

        // Add conversation history
        foreach ($conversationHistory as $msg) {
            $messages[] = [
                'role' => $msg['sender_type'] === 'user' ? 'user' : 'assistant',
                'content' => $msg['content']
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
            'Accept' => 'application/json',
        ])->timeout(60)->post('https://api.mistral.ai/v1/chat/completions', [
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
                    'provider' => 'mistral',
                    'model' => $model,
                    'tokens_used' => $data['usage']['total_tokens'] ?? null,
                ]
            ];
        }

        throw new \Exception('Mistral API error: ' . $response->body());
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
        $basePrompt = "Tu es Sophie, l'assistante virtuelle officielle de l'IMF Bisou Bisou.\n\n" .
            "TON RÔLE :\n" .
            "Tu es une experte des produits financiers de Bisou Bisou. Ton but est d'aider les clients à comprendre nos offres et à les orienter.\n\n" .
            "INFORMATIONS GÉNÉRALES :\n" .
            "- Adresse de l'institution (Microfinance) : 218, Avenue Colonel Ebeya Gombe, Kinshasa-RDC.\n\n" .
            "RÈGLES STRICTES :\n" .
            "- Tu fournis uniquement des informations à titre informatif.\n" .
            "- Tu n'inventes jamais de produits, taux, montants, conditions ou adresses.\n" .
            "- Tu utilises exclusivement les informations fournies dans le CONTEXTE DES PRODUITS ci-dessous, ou dans les INFORMATIONS GÉNÉRALES.\n" .
            "- Tu ne donnes aucun conseil financier personnalisé.\n" .
            "- Tu ne demandes aucune donnée personnelle (numéro de compte, mot de passe, etc.).\n\n" .
            "STYLE & TON :\n" .
            "- Professionnel, bienveillant et rassurant.\n" .
            "- Langage clair et simple (évite le jargon technique inutile).\n" .
            "- Adapté au public de la République Démocratique du Congo (RDC).\n" .
            "- FORMATAGE WHATSAPP: Utilise *texte* pour le gras, _texte_ pour l'italique. N'utilise JAMAIS les formats Markdown standards comme **texte** ou les titres avec #.\n\n" .
            "Si une information est manquante dans le contexte, réponds poliment que tu n'as pas cette information précise et termine en invitant le client à se rendre en agence au 218, Avenue Colonel Ebeya Gombe, Kinshasa-RDC.";

        // Add dynamic product information
        $accounts = \App\Models\Account::where('is_active', true)->get();
        $credits = \App\Models\Credit::where('is_active', true)->get();

        $productContext = "\n\nCONTEXTE DES PRODUITS DISPONIBLES :\n\n";

        $productContext .= "--- COMPTES ET ÉPARGNE ---\n";
        foreach ($accounts as $account) {
            $productContext .= "- {$account->display_name} ({$account->account_type}) : Devise {$account->currency}, Taux {$account->interest_rate}, Dépôt initial {$account->initial_deposit}, Frais de tenue {$account->maintenance_fee}.\n";
        }

        $productContext .= "\n--- CRÉDITS ET PRÊTS ---\n";
        foreach ($credits as $credit) {
            $productContext .= "- {$credit->display_name} : Montant {$credit->amount_range}, Durée {$credit->duration_range}, Taux {$credit->interest_rate}, Frais d'étude {$credit->file_fee}, Garantie: {$credit->guarantee}.\n";
        }

        // Section gestion des plaintes
        $complaintSection = "\n\n--- GESTION DES PLAINTES ---\n" .
            "Si l'utilisateur exprime une insatisfaction, un problème, une réclamation ou souhaite déposer une plainte " .
            "(exemples : \"j'ai un problème\", \"je veux me plaindre\", \"ça ne marche pas\", \"je suis mécontent\", " .
            "\"je veux faire une réclamation\"), tu dois :\n" .
            "1. Répondre avec empathie et professionnalisme.\n" .
            "2. L'informer que tu vas l'aider à enregistrer sa plainte.\n" .
            "3. Terminer OBLIGATOIREMENT ta réponse par le marqueur exact suivant sur une ligne séparée :\n" .
            "   [INITIATE_COMPLAINT_FLOW]\n\n" .
            "Ne génère ce marqueur que si l'utilisateur exprime clairement une plainte ou une insatisfaction. " .
            "Pour une simple question ou une demande d'information, réponds normalement sans ce marqueur.";

        return $basePrompt . $productContext . $complaintSection;
    }

    /**
     * Format standard Markdown to WhatsApp compatible text.
     */
    protected function formatForWhatsApp(string $text): string
    {
        // 1. Convert headers ### Titre to *Titre*
        $text = preg_replace('/^#{1,6}\s+(.*?)$/m', '*$1*', $text);
        
        // 2. Reduce multiple formatting chars to single for WhatsApp
        // **bold** -> *bold*, ** -> *
        $text = preg_replace('/\*{2,}/', '*', $text);
        // __italic__ -> _italic_
        $text = preg_replace('/_{2,}/', '_', $text);
        
        // 3. Remove horizontal rules ---
        $text = preg_replace('/^[-_*]{3,}$/m', '', $text);
        
        // 4. Replace markdown links [text](url) with text: url
        $text = preg_replace('/\[(.*?)\]\((.*?)\)/', '$1: $2', $text);
        
        // 5. Remove extra empty lines
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        
        return trim($text);
    }
}
