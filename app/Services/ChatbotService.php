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
    public function processMessage(string $userIdentifier, string $messageContent, string $platform = 'whatsapp', array $parsedData = [])
    {
        try {
            // Get or create conversation
            $conversation = Conversation::getOrCreate($userIdentifier, $platform);

            // Save user message
            $userMessage = $this->saveMessage($conversation->id, 'user', $messageContent);

            $metadata = $conversation->metadata ?? [];

            // 0. Traiter les réponses aux boutons de session en pause
            $buttonId = $parsedData['button_id'] ?? null;
            if ($buttonId === 'resume_session') {
                if (isset($metadata['paused_flow'])) {
                    $metadata['complaint_flow'] = $metadata['paused_flow'];
                    unset($metadata['paused_flow']);
                    unset($metadata['paused_flow_name']);
                    unset($metadata['pending_message']);
                    $conversation->update(['metadata' => $metadata]);
                    
                    // Renvoyer un message pour inciter l'utilisateur à continuer
                    $step = $metadata['complaint_flow']['step'] ?? '';
                    $msg = "✅ Session reprise.\n\n";
                    if ($step === 'awaiting_description') {
                        $msg .= "Pouvez-vous décrire votre problème en détail ?";
                    } else {
                        $msg .= "Veuillez continuer là où vous vous étiez arrêté(e).";
                    }

                    $this->saveMessage($conversation->id, 'bot', $msg);
                    $conversation->update(['last_message_at' => now()]);

                    return [
                        'success' => true,
                        'response' => $msg,
                        'conversation_id' => $conversation->id,
                        'send_as_flow' => false,
                    ];
                }
            } elseif ($buttonId === 'cancel_session') {
                if (isset($metadata['paused_flow'])) {
                    $pendingMessage = $metadata['pending_message'] ?? null;
                    
                    unset($metadata['paused_flow']);
                    unset($metadata['paused_flow_name']);
                    unset($metadata['pending_message']);
                    $conversation->update(['metadata' => $metadata]);
                    
                    if ($pendingMessage) {
                        // Confirmer l'annulation avant de traiter le message
                        $webhookService = app(\App\Services\WebhookService::class);
                        $webhookService->sendWhatsAppMessage($userIdentifier, "✅ Session annulée. Je traite votre nouvelle demande...");
                        
                        // Remplacer le message actuel (bouton) par le message en attente
                        $messageContent = $pendingMessage;
                        
                        // Laisser le code continuer vers la suite (IA)
                    } else {
                        $msg = "✅ Session annulée. Que souhaitez-vous faire ?";
                        $this->saveMessage($conversation->id, 'bot', $msg);
                        $conversation->update(['last_message_at' => now()]);

                        return [
                            'success' => true,
                            'response' => $msg,
                            'conversation_id' => $conversation->id,
                            'send_as_flow' => false,
                        ];
                    }
                }
            }

            // 1. Mettre en pause le flux si la conversation est inactive depuis un moment (ex: 1 heure)
            // On le fait si la conversation était dans un flux ET qu'il y a de l'inactivité.
            // On peut aussi le faire systématiquement si le message ne correspond pas au flux attendu, 
            // mais l'inactivité est plus sûre pour commencer.
            if ($conversation->last_message_at && $conversation->last_message_at->diffInHours(now()) >= 1) {
                if (isset($metadata['complaint_flow'])) {
                    $metadata['paused_flow'] = $metadata['complaint_flow'];
                    $metadata['paused_flow_name'] = 'Plainte';
                    unset($metadata['complaint_flow']);
                    $conversation->update(['metadata' => $metadata]);
                }
            }

            // 2. Si la session est en pause, envoyer les boutons et stopper le traitement
            if (isset($metadata['paused_flow'])) {
                // Sauvegarder le message texte pour pouvoir le traiter s'ils choisissent "Nouvelle opération"
                if (!isset($parsedData['button_id']) && !isset($parsedData['interactive_type'])) {
                    $metadata['pending_message'] = $messageContent;
                    $conversation->update(['metadata' => $metadata]);
                }

                $operationName = $metadata['paused_flow_name'] ?? 'Opération';
                
                $webhookService = app(\App\Services\WebhookService::class);
                $webhookService->sendButtons(
                    $userIdentifier,
                    "⚠️ *Session en pause*\n\nVous n'avez pas terminé votre précédente opération (*{$operationName}*).\n\nVoulez-vous la reprendre ou lancer une nouvelle opération ?",
                    [
                        [
                            'type' => 'reply',
                            'reply' => [
                                'id' => 'resume_session',
                                'title' => 'Reprendre'
                            ]
                        ],
                        [
                            'type' => 'reply',
                            'reply' => [
                                'id' => 'cancel_session',
                                'title' => 'Nouvelle opération'
                            ]
                        ]
                    ]
                );

                return [
                    'success' => true,
                    'response' => '', // Pas de texte, on a envoyé des boutons
                    'conversation_id' => $conversation->id,
                    'send_as_flow' => false,
                ];
            }

            // 3. Vérifier si un flux de plainte est en cours
            $complaintFlow = $conversation->metadata['complaint_flow'] ?? null;

            if ($complaintFlow) {
                $botResponse = $this->handleComplaintFlow($conversation, $messageContent, $complaintFlow, $parsedData);

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

            // 4. Gérer les messages d'accueil pour éviter un appel IA inutile
            $welcomeService = app(\App\Services\WelcomeService::class);
            if ($welcomeService->isGreetingRequest($messageContent)) {
                $greetingText = "Bonjour 👋🏽, je suis Sophie, assistante virtuelle de l'IMF Bisou Bisou.\n\nComment puis-je vous aider aujourd'hui ?\n\nJe peux vous renseigner sur nos comptes, crédits et services.";
                
                $botMessage = $this->saveMessage(
                    $conversation->id,
                    'bot',
                    $greetingText
                );

                $conversation->update(['last_message_at' => now()]);

                return [
                    'success' => true,
                    'response' => $greetingText,
                    'conversation_id' => $conversation->id,
                ];
            }

            // 5. Sinon : flux normal → appel IA
            $aiResponse = $this->generateAIResponse($conversation, $messageContent);

            $responseContent = $aiResponse['content'];
            $responseContent = $this->formatForWhatsApp($responseContent);

            // 3. Détecter le marqueur de déclenchement de plainte dans la réponse IA
            if (str_contains($responseContent, '[INITIATE_COMPLAINT_FLOW]')) {
                // Nettoyer le marqueur de la réponse visible
                $responseContent = trim(str_replace('[INITIATE_COMPLAINT_FLOW]', '', $responseContent));

                $token = bin2hex(random_bytes(16));
                
                // Sauvegarder le token dans le cache pour 10 minutes avec les infos de conversation
                \Illuminate\Support\Facades\Cache::put('complaint_token_' . $token, [
                    'user_identifier' => $conversation->user_identifier,
                    'conversation_id' => $conversation->id
                ], now()->addMinutes(10));
                
                // Initialiser le flux de plainte dans la metadata avec le token
                $metadata = $conversation->metadata ?? [];
                $metadata['form_token'] = $token;
                $metadata['complaint_flow'] = [
                    'step' => 'awaiting_flow_submission',
                    'subject' => null,
                    'description' => null,
                ];
                $conversation->update(['metadata' => $metadata]);

                // Save bot message before returning
                $botMessage = $this->saveMessage(
                    $conversation->id,
                    'bot',
                    $responseContent,
                    $aiResponse['metadata']
                );

                // Mise à jour du timestamp de la conversation
                $conversation->update(['last_message_at' => now()]);

                // Demander l'envoi de l'URL CTA au lieu du texte normal
                return [
                    'success' => true,
                    'response' => $responseContent,
                    'conversation_id' => $conversation->id,
                    'send_as_cta_url' => true,
                    'form_token' => $token
                ];
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
    private function handleComplaintFlow(Conversation $conversation, string $userMessage, array $flow, array $parsedData): string
    {
        $step = $flow['step'];

        if ($step === 'awaiting_flow_submission') {
            $isFlowReply = ($parsedData['interactive_type'] ?? '') === 'nfm_reply' || ($parsedData['interactive_type'] ?? '') === 'web_form_reply';
            
            if ($isFlowReply && isset($parsedData['flow_data'])) {
                $flowData = $parsedData['flow_data'];
                $subCategoryCode = $flowData['sub_category'] ?? null;
                $description = $flowData['description'] ?? '';
                $urgency = $flowData['urgency'] ?? 'medium';
                
                try {
                    $complaintService = app(ComplaintService::class);
                    $ticket = $complaintService->createFromFlowData($conversation, $subCategoryCode, $description, $urgency);
                    
                    // Clore le flux
                    $metadata = $conversation->metadata;
                    unset($metadata['complaint_flow']);
                    $conversation->update(['metadata' => $metadata]);

                    return "Merci. Votre demande a été enregistrée sous la référence *" . $ticket->reference . "*. Notre équipe l'examinera très bientôt.";
                } catch (\Exception $e) {
                    Log::error('Error creating complaint from flow: ' . $e->getMessage());
                    return "Désolée, une erreur est survenue lors de l'enregistrement de votre demande. Veuillez réessayer.";
                }
            } else {
                // FALLBACK: L'utilisateur a envoyé du texte au lieu de remplir le formulaire.
                $metadata = $conversation->metadata;
                $metadata['paused_flow'] = $metadata['complaint_flow'];
                $metadata['paused_flow_name'] = 'Plainte';
                unset($metadata['complaint_flow']);
                $conversation->update(['metadata' => $metadata]);

                $webhookService = app(\App\Services\WebhookService::class);
                $webhookService->sendButtons(
                    $conversation->user_identifier,
                    "⚠️ Opération en attente\n\nVous avez une *Plainte* en cours. Pour la finaliser, vous devez remplir le formulaire (via le bouton envoyé plus haut).\n\nSouhaitez-vous reprendre cette opération ou tout annuler ?",
                    [
                        ['type' => 'reply', 'reply' => ['id' => 'resume_session', 'title' => 'Reprendre']],
                        ['type' => 'reply', 'reply' => ['id' => 'cancel_session', 'title' => 'Nouvelle opération']]
                    ]
                );

                return "";
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
        } elseif ($provider === 'claude') {
            return $this->generateClaudeResponse($conversationHistory, $userMessage);
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
     * Generate response using Anthropic Claude
     */
    protected function generateClaudeResponse(array $conversationHistory, string $userMessage)
    {
        $apiKey = config('chatbot.claude_api_key');
        $model = config('chatbot.claude_model', 'claude-3-5-sonnet-20240620');

        $messages = [];

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
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'system' => $this->getSystemPrompt(),
            'messages' => $messages,
            'max_tokens' => 500,
            'temperature' => 0.7,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'content' => $data['content'][0]['text'] ?? '',
                'metadata' => [
                    'provider' => 'claude',
                    'model' => $model,
                    'tokens_used' => $data['usage']['input_tokens'] + $data['usage']['output_tokens'] ?? null,
                ]
            ];
        }

        throw new \Exception('Claude API error: ' . $response->body());
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
