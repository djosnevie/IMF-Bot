# Guide de Diagnostic - Madame Sophie

## Problème : Pas de réponse du bot

### Checklist de diagnostic

#### 1. Vérifier que le serveur Laravel tourne ✅
```bash
ps aux | grep "php artisan serve"
```
**Résultat attendu** : Processus actif sur port 8000

#### 2. Vérifier que ngrok tourne ✅
```bash
ps aux | grep ngrok
```
**Résultat attendu** : Processus actif

#### 3. Obtenir l'URL ngrok 🔍
```bash
curl -s http://localhost:4040/api/tunnels | grep -o '"public_url":"[^"]*"' | head -1
```
**OU** Ouvrir dans le navigateur : `http://localhost:4040`

#### 4. Tester le webhook localement ✅
```bash
curl 'http://localhost:8000/webhook?hub.mode=subscribe&hub.verify_token=bisou_bisou_webhook_2024&hub.challenge=test123'
```
**Résultat attendu** : `test123`

#### 5. Vérifier la configuration Meta Developer Console 🔍

**URL du webhook** : `https://VOTRE-URL-NGROK.ngrok.io/webhook`

**Points à vérifier** :
- [ ] URL correcte (avec `/webhook` à la fin)
- [ ] Token de vérification : `bisou_bisou_webhook_2024`
- [ ] Champs activés : `messages` et `message_status`
- [ ] Webhook vérifié avec succès (✅ vert)

#### 6. Vérifier les variables d'environnement
```bash
cat .env | grep WHATSAPP
```
**Doit contenir** :
```
WHATSAPP_VERIFY_TOKEN=bisou_bisou_webhook_2024
WHATSAPP_ACCESS_TOKEN=EAAPNhJ9FdUs...
WHATSAPP_PHONE_NUMBER_ID=866039169917163
```

#### 7. Vérifier les logs webhook
```bash
mysql -u root -proot -e "SELECT * FROM webhook_logs ORDER BY created_at DESC LIMIT 5;" imf_bot
```
**Si vide** : Les webhooks n'arrivent pas au serveur

#### 8. Tester avec un message WhatsApp réel

**Envoyer** : "bonjour" au numéro WhatsApp Business

**Vérifier immédiatement** :
```bash
# Logs Laravel
tail -f storage/logs/laravel.log

# Logs webhook
mysql -u root -proot -e "SELECT * FROM webhook_logs ORDER BY created_at DESC LIMIT 1;" imf_bot
```

## Solutions aux problèmes courants

### Problème : Ngrok sur mauvais port
**Symptôme** : ngrok tourne sur 8001 mais Laravel sur 8000

**Solution** :
```bash
# Arrêter ngrok
killall ngrok

# Redémarrer sur le bon port
ngrok http 8000
```

### Problème : URL ngrok changée
**Symptôme** : Webhook ne reçoit rien

**Solution** :
1. Obtenir nouvelle URL ngrok : `http://localhost:4040`
2. Mettre à jour dans Meta Developer Console
3. Re-vérifier le webhook

### Problème : Token de vérification incorrect
**Symptôme** : Erreur 403 lors de la vérification

**Solution** :
Vérifier que le token dans `.env` et Meta Console sont identiques

### Problème : Pas de logs
**Symptôme** : Aucun log dans `webhook_logs`

**Solution** :
1. Vérifier que l'URL ngrok est correcte
2. Vérifier que le webhook est activé dans Meta Console
3. Envoyer un message test

## Test complet

### 1. Redémarrer tout
```bash
# Terminal 1 : Laravel
php artisan serve

# Terminal 2 : Ngrok
ngrok http 8000
```

### 2. Configurer Meta Console
1. Copier l'URL ngrok (ex: `https://abc123.ngrok.io`)
2. Aller dans Meta Developer Console > WhatsApp > Configuration
3. URL webhook : `https://abc123.ngrok.io/webhook`
4. Token : `bisou_bisou_webhook_2024`
5. Vérifier

### 3. Tester
Envoyer "bonjour" au numéro WhatsApp Business

### 4. Vérifier réception
```bash
mysql -u root -proot -e "SELECT COUNT(*) FROM webhook_logs;" imf_bot
```
**Doit être > 0**
