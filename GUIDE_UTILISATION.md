# Guide d'utilisation - Madame Sophie

## Message d'accueil

Lorsqu'un utilisateur envoie un message à Madame Sophie, il reçoit automatiquement le message d'accueil avec 3 boutons interactifs.

### Déclencheurs du message d'accueil

Le message d'accueil s'affiche quand l'utilisateur tape :
- `bonjour`
- `salut`
- `hello`
- `menu`
- `accueil`
- `retour`
- `start`

### Structure du message

```
Bonjour 👋

Je suis Madame Sophie, l'assistante virtuelle de l'IMF Bisou Bisou.

Veuillez choisir une option :

[1️⃣ À propos de l'IMF]
[2️⃣ Produits & Services]
[3️⃣ Aide & Orientation]
```

## Réponses aux boutons

### 1️⃣ À propos de l'IMF
Informations sur l'institution :
- Présentation de Bisou Bisou
- Valeurs (Proximité, Transparence, Accompagnement)
- Mission d'inclusion financière

### 2️⃣ Produits & Services
Liste des produits :
- **Prêts** : personnels, professionnels, agricoles
- **Épargne** : comptes d'épargne, épargne programmée
- **Autres services** : transferts, conseils financiers

### 3️⃣ Aide & Orientation
Assistance et contact :
- Types d'aide disponibles
- Informations de contact
- Numéro de téléphone pour parler à un conseiller

## Retour au menu

À tout moment, l'utilisateur peut taper **"menu"** pour revenir au menu principal avec les 3 boutons.

## Flux de conversation

```
User: "Bonjour"
Bot: [Affiche message d'accueil avec 3 boutons]

User: [Clique sur "Produits & Services"]
Bot: [Affiche la liste des produits]

User: "menu"
Bot: [Affiche à nouveau le message d'accueil avec 3 boutons]
```

## Messages libres (IA)

Si l'utilisateur envoie un message qui n'est pas un mot-clé menu et ne clique pas sur un bouton, le message est traité par l'IA (OpenAI/Gemini) si configurée.

**Note** : Pour l'instant, sans clé IA configurée, le bot répondra avec un message d'erreur par défaut.
