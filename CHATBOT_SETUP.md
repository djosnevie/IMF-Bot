# Configuration du Chatbot Madame Sophie

## Variables d'environnement à ajouter dans .env

```env
# Chatbot Configuration
CHATBOT_NAME="Madame Sophie"
CHATBOT_AI_PROVIDER=openai  # ou "gemini"

# OpenAI Configuration (si vous utilisez OpenAI)
OPENAI_API_KEY=votre_cle_api_openai
OPENAI_MODEL=gpt-4  # ou gpt-3.5-turbo pour réduire les coûts

# Google Gemini Configuration (si vous utilisez Gemini)
GEMINI_API_KEY=votre_cle_api_gemini
GEMINI_MODEL=gemini-pro

# WhatsApp Business API Configuration
WHATSAPP_VERIFY_TOKEN=votre_token_de_verification_personnalise
WHATSAPP_ACCESS_TOKEN=votre_access_token_whatsapp
WHATSAPP_PHONE_NUMBER_ID=votre_phone_number_id
WHATSAPP_APP_SECRET=votre_app_secret

# Optional: Custom System Prompt
# CHATBOT_SYSTEM_PROMPT="Votre prompt personnalisé..."
```

## Configuration du Webhook WhatsApp

### URL du Webhook
Votre URL de webhook sera : `https://votre-domaine.com/webhook`

### Étapes de configuration dans Meta Developer Console

1. **Créer une application WhatsApp Business**
   - Allez sur https://developers.facebook.com/
   - Créez une nouvelle application
   - Ajoutez le produit "WhatsApp"

2. **Configurer le webhook**
   - Dans la section WhatsApp > Configuration
   - URL de callback : `https://votre-domaine.com/webhook`
   - Token de vérification : La valeur de `WHATSAPP_VERIFY_TOKEN`
   - Abonnez-vous aux événements : `messages`

3. **Obtenir les credentials**
   - `WHATSAPP_ACCESS_TOKEN` : Token d'accès temporaire ou permanent
   - `WHATSAPP_PHONE_NUMBER_ID` : ID du numéro de téléphone
   - `WHATSAPP_APP_SECRET` : Secret de l'application

4. **Tester le webhook**
   ```bash
   # Test de vérification (GET)
   curl "http://localhost:8000/webhook?hub.mode=subscribe&hub.verify_token=VOTRE_TOKEN&hub.challenge=test123"
   
   # Test d'envoi de message (POST)
   curl -X POST http://localhost:8000/webhook \
     -H "Content-Type: application/json" \
     -d '{
       "entry": [{
         "changes": [{
           "value": {
             "messages": [{
               "from": "221771234567",
               "id": "wamid.test123",
               "timestamp": "1234567890",
               "type": "text",
               "text": {
                 "body": "Bonjour Madame Sophie"
               }
             }],
             "contacts": [{
               "profile": {
                 "name": "Test User"
               }
             }]
           }
         }]
       }]
     }'
   ```

## Routes disponibles

### Webhook (Public)
- `GET /webhook` - Vérification du webhook
- `POST /webhook` - Réception des messages WhatsApp

### Admin (Protégé par auth)
- `GET /chatbot/conversations` - Liste des conversations
- `GET /chatbot/conversations/{id}` - Détails d'une conversation
- `GET /chatbot/stats` - Statistiques du chatbot

## Déploiement

### Ngrok pour tests locaux
```bash
ngrok http 8000
# Utilisez l'URL HTTPS générée comme URL de webhook
```

### Production
- Assurez-vous que votre serveur est accessible via HTTPS
- Configurez les variables d'environnement
- Exécutez les migrations : `php artisan migrate`
- Configurez le webhook dans Meta Developer Console
