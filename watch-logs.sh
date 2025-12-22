#!/bin/bash

# Script pour voir les logs en temps réel

echo "🔍 Surveillance des logs Madame Sophie..."
echo "=========================================="
echo ""

# Créer le fichier de log s'il n'existe pas
touch storage/logs/laravel.log

# Suivre les logs en temps réel
tail -f storage/logs/laravel.log | grep --line-buffered -E "WEBHOOK|MESSAGE|DONNÉES|Envoi|Clic|Traitement|📨|💬|📋|🏠|🔘|🤖"
