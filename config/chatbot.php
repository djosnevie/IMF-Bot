<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Chatbot Name
    |--------------------------------------------------------------------------
    |
    | The name of your chatbot
    |
    */
    'name' => env('CHATBOT_NAME', 'Madame Sophie'),

    /*
    |--------------------------------------------------------------------------
    | AI Provider
    |--------------------------------------------------------------------------
    |
    | Supported: "openai", "gemini", "mistral"
    |
    */
    'ai_provider' => env('CHATBOT_AI_PROVIDER', 'openai'),

    /*
    |--------------------------------------------------------------------------
    | OpenAI Configuration
    |--------------------------------------------------------------------------
    */
    'openai_api_key' => env('OPENAI_API_KEY'),
    'openai_model' => env('OPENAI_MODEL', 'gpt-4'),

    /*
    |--------------------------------------------------------------------------
    | Google Gemini Configuration
    |--------------------------------------------------------------------------
    */
    'gemini_api_key' => env('GEMINI_API_KEY'),
    'gemini_model' => env('GEMINI_MODEL', 'gemini-pro'),

    /*
    |--------------------------------------------------------------------------
    | Mistral Configuration
    |--------------------------------------------------------------------------
    */
    'mistral_api_key' => env('MISTRAL_API_KEY'),
    'mistral_model' => env('MISTRAL_MODEL', 'mistral-large-latest'),

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Business API Configuration
    |--------------------------------------------------------------------------
    */
    'whatsapp_verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
    'whatsapp_access_token' => env('WHATSAPP_ACCESS_TOKEN'),
    'whatsapp_phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    'whatsapp_app_secret' => env('WHATSAPP_APP_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | System Prompt
    |--------------------------------------------------------------------------
    |
    | The system prompt that defines Madame Sophie's personality and role
    |
    */
    'system_prompt' => env(
        'CHATBOT_SYSTEM_PROMPT',
        "Tu es Madame Sophie, une assistante virtuelle bienveillante et professionnelle pour Bisou Bisou, " .
        "une institution de microfinance située au 218, Avenue Colonel Ebeya Gombe, Kinshasa-RDC. Tu aides les clients avec des informations sur les prêts, " .
        "l'épargne, les conditions d'éligibilité, et les procédures. Tu es toujours polie, claire et concise. " .
        "Tu réponds en français et tu es là pour faciliter l'accès aux services financiers. " .
        "Tu peux répondre aux questions sur : " .
        "- Les types de prêts disponibles (prêts personnels, prêts professionnels, prêts agricoles) " .
        "- Les conditions d'éligibilité " .
        "- Les taux d'intérêt " .
        "- Les procédures de demande " .
        "- Les comptes d'épargne " .
        "- Notre adresse physique (218, Avenue Colonel Ebeya Gombe, Kinshasa-RDC) et heures d'ouverture"
    ),

    /*
    |--------------------------------------------------------------------------
    | Bisou Bisou Information
    |--------------------------------------------------------------------------
    |
    | Information about the microfinance institution
    |
    */
    'institution' => [
        'name' => 'Bisou Bisou',
        'type' => 'Microfinance',
        'services' => [
            'Prêts personnels',
            'Prêts professionnels',
            'Prêts agricoles',
            'Comptes d\'épargne',
            'Transferts d\'argent',
        ],
    ],
];
