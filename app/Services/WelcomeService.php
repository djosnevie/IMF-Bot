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
        $bodyText = "Bonjour 👋\n" .
            "Je suis Sophie, l’assistante virtuelle de l’IMF Bisou Bisou.\n" .
            "Comment puis-je vous aider ?\n\n" .
            "👉 Tapez *Menu* pour afficher les options.";

        return $this->webhookService->sendWhatsAppMessage($userIdentifier, $bodyText);
    }

    /**
     * Send main menu buttons
     */
    public function sendWelcomeMessage(string $userIdentifier): bool
    {
        $buttons = [
            [
                'type' => 'reply',
                'reply' => [
                    'id' => 'btn_about',
                    'title' => 'À propos de l’IMF'
                ]
            ],
            [
                'type' => 'reply',
                'reply' => [
                    'id' => 'btn_products',
                    'title' => 'Produits & Services'
                ]
            ],
            [
                'type' => 'reply',
                'reply' => [
                    'id' => 'btn_help',
                    'title' => 'Aide & Orientation'
                ]
            ]
        ];

        $bodyText = "Veuillez choisir une option dans le menu ci-dessous :";
        $headerText = "Menu Principal 📋";

        return $this->webhookService->sendButtons(
            $userIdentifier,
            $bodyText,
            $buttons,
            $headerText
        );
    }

    /**
     * Handle button click responses
     */
    public function handleButtonClick(string $buttonId, string $userIdentifier): bool
    {
        $responseText = match ($buttonId) {
            'btn_about' => $this->getAboutInfo(),
            'btn_products' => $this->getProductsInfo(),
            'btn_help' => $this->getHelpInfo(),
            'btn_menu' => '', // Retour au menu

            // Anciens boutons (gardés pour compatibilité ou autres flux)
            'btn_credit' => $this->getCreditInfo(),
            'btn_likelemba' => $this->getLikelembaInfo(),

            // Nouveau flux Épargne (Liste)
            'btn_epargne' => 'submenu_epargne_list',

            // Choix de la liste Épargne
            'list_epargne_ind' => $this->getAccountTypeInfo('Savings Account', 'Compte Épargne Individuel', 'individual'),
            'list_epargne_ent' => $this->getAccountTypeInfo('Savings Account', 'Compte Épargne Entreprise', 'business'),
            'list_courant_ind' => $this->getAccountTypeInfo('Current Account', 'Compte Courant Individuel', 'individual'),
            'list_courant_ent' => $this->getAccountTypeInfo('Current Account', 'Compte Courant Entreprise', 'business'), // Note: Pas encore de données business pour courant dans le seeder, mais on prépare
            'list_compte_salaire' => $this->getAccountTypeInfo('Salary Account', 'Compte Salaire'),
            'list_compte_collectif' => $this->getAccountTypeInfo('Collective Account', 'Compte Collectif'),
            'list_dat' => $this->getAccountTypeInfo('Fixed Deposit', 'Dépôt à Terme'),

            default => "Désolée, je n'ai pas compris votre choix."
        };

        // Si c'est un retour au menu, envoyer le message d'accueil
        if ($buttonId === 'btn_menu') {
            return $this->sendWelcomeMessage($userIdentifier);
        }

        // Si c'est le menu produits, envoyer le sous-menu boutons
        if ($responseText === 'submenu_products') {
            return $this->sendProductsSubmenu($userIdentifier);
        }

        // Si c'est le menu épargne, envoyer la liste
        if ($responseText === 'submenu_epargne_list') {
            return $this->sendEpargneList($userIdentifier);
        }

        // Sinon, envoyer la réponse avec un bouton "Revenir au Menu"
        return $this->sendResponseWithMenuButton($userIdentifier, $responseText);
    }

    /**
     * Send Épargne List Menu
     */
    public function sendEpargneList(string $userIdentifier): bool
    {
        $sections = [
            [
                'title' => 'Nos Produits',
                'rows' => [
                    [
                        'id' => 'list_epargne_ind',
                        'title' => 'Épargne Individuel',
                        'description' => 'Pour les particuliers'
                    ],
                    [
                        'id' => 'list_epargne_ent',
                        'title' => 'Épargne Entreprise',
                        'description' => 'Pour les sociétés'
                    ],
                    [
                        'id' => 'list_courant_ind',
                        'title' => 'Courant Individuel',
                        'description' => 'Gestion quotidienne perso'
                    ],
                    [
                        'id' => 'list_courant_ent',
                        'title' => 'Courant Entreprise', // Titre raccourci pour limite 24 chars
                        'description' => 'Gestion quotidienne pro'
                    ],
                    [
                        'id' => 'list_compte_salaire',
                        'title' => 'Compte Salaire',
                        'description' => 'Domiciliation salaire'
                    ],
                    [
                        'id' => 'list_compte_collectif',
                        'title' => 'Compte Collectif',
                        'description' => 'Groupes et associations'
                    ],
                    [
                        'id' => 'list_dat',
                        'title' => 'Dépôt à Terme',
                        'description' => 'Placement bloqué'
                    ]
                ]
            ]
        ];

        return $this->webhookService->sendListMessage(
            $userIdentifier,
            "Découvrez notre gamme complète. Sélectionnez une option :",
            "Voir la liste",
            $sections,
            "💰 Menu Épargne"
        );
    }

    /**
     * Get info for a specific account type from DB
     */
    protected function getAccountTypeInfo(string $dbType, string $displayName, ?string $category = null): string
    {
        $query = \App\Models\Account::where('account_type', $dbType)
            ->where('is_active', true);

        if ($category) {
            $query->where('category', $category);
        }

        $accounts = $query->orderBy('display_order')->get();

        if ($accounts->isEmpty()) {
            return "ℹ️ *{$displayName}*\n\n" .
                "Aucun produit disponible pour le moment dans cette catégorie.\n" .
                "Contactez-nous pour plus d'informations !";
        }

        $message = "💰 *{$displayName} - Bisou Bisou*\n\nVoici nos offres :\n\n";

        foreach ($accounts as $account) {
            $message .= "📌 *" . $account->display_name . "* (" . $account->currency . ")\n";
            $message .= "• Catégorie : " . ucfirst($account->category) . "\n";
            if ($account->interest_rate && $account->interest_rate !== 'Non rémunéré') {
                $message .= "• Taux : " . $account->interest_rate . "\n";
            }
            $message .= "• Dépôt min : " . $account->initial_deposit . "\n";
            $message .= "• Frais tenue : " . $account->maintenance_fee . "\n";

            if ($account->duration && $account->duration !== '-') {
                $message .= "• Durée : " . $account->duration . "\n";
            }
            $message .= "\n";
        }

        $message .= "Pour souscrire, rendez-vous en agence !";

        return $message;
    }

    /**
     * Send response with "Revenir au Menu" button
     */
    public function sendResponseWithMenuButton(string $userIdentifier, string $message): bool
    {
        $buttons = [
            [
                'type' => 'reply',
                'reply' => [
                    'id' => 'btn_menu',
                    'title' => 'Revenir au Menu'
                ]
            ]
        ];

        return $this->webhookService->sendButtons(
            $userIdentifier,
            $message,
            $buttons
        );
    }

    /**
     * Get information about IMF Bisou Bisou
     */
    protected function getAboutInfo(): string
    {
        return "🏦 *À propos de l'IMF Bisou Bisou*\n\n" .
            "Bisou Bisou est une institution de microfinance dédiée à l'inclusion financière. " .
            "Nous offrons des services financiers accessibles pour soutenir les entrepreneurs " .
            "et les familles dans leurs projets.\n\n" .
            "📍 *Nos valeurs :*\n" .
            "• Proximité avec nos clients\n" .
            "• Transparence dans nos services\n" .
            "• Accompagnement personnalisé";
    }

    /**
     * Get products and services information (sends submenu)
     */
    protected function getProductsInfo(): string
    {
        // This will be handled differently - return empty to trigger submenu
        return 'submenu_products';
    }

    /**
     * Send products submenu with 3 options
     */
    public function sendProductsSubmenu(string $userIdentifier): bool
    {
        $buttons = [
            [
                'type' => 'reply',
                'reply' => [
                    'id' => 'btn_epargne',
                    'title' => 'Épargne'
                ]
            ],
            [
                'type' => 'reply',
                'reply' => [
                    'id' => 'btn_credit',
                    'title' => 'Crédit'
                ]
            ],
            [
                'type' => 'reply',
                'reply' => [
                    'id' => 'btn_likelemba',
                    'title' => 'Likelemba'
                ]
            ]
        ];

        $bodyText = "Choisissez le produit qui vous intéresse :";
        $headerText = "Nos Produits & Services";

        return $this->webhookService->sendButtons(
            $userIdentifier,
            $bodyText,
            $buttons,
            $headerText
        );
    }

    /**
     * Get help and orientation information
     */
    protected function getHelpInfo(): string
    {
        return "🆘 *Aide & Orientation*\n\n" .
            "Je peux vous aider avec :\n\n" .
            "• Informations sur nos produits\n" .
            "• Conditions d'éligibilité\n" .
            "• Procédures de demande\n" .
            "• Localisation de nos agences\n" .
            "• Questions générales\n\n" .
            "📞 *Contact direct :*\n" .
            "Pour parler à un conseiller :\n" .
            "Tel: +243 XXX XXX XXX\n\n" .
            "N'hésitez pas à me poser vos questions !";
    }

    /**
     * Get Épargne information
     */
    protected function getEpargneInfo(): string
    {
        $accounts = \App\Models\Account::whereIn('account_type', ['Savings Account', 'Fixed Deposit'])
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();

        if ($accounts->isEmpty()) {
            return "💰 *Épargne - Bisou Bisou*\n\n" .
                "Nos solutions d'épargne sont en cours de mise à jour.\n" .
                "Contactez-nous pour plus d'informations !";
        }

        $message = "💰 *Épargne - Bisou Bisou*\n\nNos solutions d'épargne :\n\n";

        foreach ($accounts as $account) {
            $message .= "📌 *" . $account->display_name . "* (" . $account->currency . ")\n";
            $message .= "• Taux : " . $account->interest_rate . "\n";
            $message .= "• Dépôt min : " . $account->initial_deposit . "\n";
            if ($account->duration && $account->duration !== '-') {
                $message .= "• Durée : " . $account->duration . "\n";
            }
            $message .= "\n";
        }

        $message .= "Pour ouvrir un compte, contactez-nous !";

        return $message;
    }

    /**
     * Get Crédit information
     */
    protected function getCreditInfo(): string
    {
        return "💳 *Crédit - Bisou Bisou*\n\n" .
            "Nos offres de crédit :\n\n" .
            "📌 *Prêt personnel*\n" .
            "• Montant : jusqu'à 5 000 000 FC\n" .
            "• Durée : 3 à 24 mois\n" .
            "• Taux compétitif\n\n" .
            "📌 *Prêt professionnel*\n" .
            "• Pour développer votre activité\n" .
            "• Accompagnement personnalisé\n" .
            "• Conditions flexibles\n\n" .
            "📌 *Prêt agricole*\n" .
            "• Spécial agriculteurs\n" .
            "• Calendrier adapté aux récoltes\n\n" .
            "Contactez-nous pour une demande !";
    }

    /**
     * Get Likelemba information
     */
    protected function getLikelembaInfo(): string
    {
        $accounts = \App\Models\Account::where('account_type', 'Collective Account')
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();

        if ($accounts->isEmpty()) {
            return "🤝 *Likelemba - Bisou Bisou*\n\n" .
                "Le Likelemba est une tontine moderne !\n\n" .
                "📌 *Comment ça marche ?*\n" .
                "• Groupe de 5 à 20 personnes\n" .
                "• Cotisations régulières\n" .
                "• Chacun reçoit la cagnotte à tour de rôle\n\n" .
                "Intéressé ? Contactez-nous !";
        }

        $message = "🤝 *Likelemba - Bisou Bisou*\n\nLe Likelemba est une tontine moderne !\n\n";

        foreach ($accounts as $account) {
            $message .= "📌 *" . $account->display_name . "* (" . $account->currency . ")\n";
            $message .= "• Dépôt initial : " . $account->initial_deposit . "\n";
            $message .= "• Frais : " . $account->maintenance_fee . "\n";
            $message .= "\n";
        }

        $message .= "📌 *Avantages*\n" .
            "• Épargne collective\n" .
            "• Solidarité entre membres\n" .
            "• Gestion sécurisée par Bisou Bisou\n\n" .
            "Intéressé ? Contactez-nous !";

        return $message;
    }

    /**
     * Check if message is a menu request (strict)
     */
    public function isMenuRequest(string $message): bool
    {
        return strtolower(trim($message)) === 'menu';
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
