#!/bin/bash

#############################################
# Script de déploiement faktur.lu
# Usage: ./deploy.sh [quick]
#   - Sans argument : déploiement complet
#   - quick : git pull + cache clear seulement
#############################################

set -e  # Arrêter en cas d'erreur

# Configuration
SSH_USER="sc1beal9117"
SSH_HOST="saut.o2switch.net"
SSH_PORT="22"
REMOTE_PATH="/home2/sc1beal9117/faktur.lu"
BRANCH="main"

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  Déploiement faktur.lu${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

# Vérifier qu'on est sur la bonne branche localement
CURRENT_BRANCH=$(git branch --show-current)
if [ "$CURRENT_BRANCH" != "$BRANCH" ]; then
    echo -e "${YELLOW}Attention: Vous êtes sur la branche '$CURRENT_BRANCH', pas '$BRANCH'${NC}"
    read -p "Continuer quand même? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# Vérifier les modifications non commitées
if [ -n "$(git status --porcelain)" ]; then
    echo -e "${RED}Erreur: Vous avez des modifications non commitées${NC}"
    git status --short
    exit 1
fi

# Pousser les modifications locales
echo -e "${YELLOW}[1/5] Push des modifications vers GitHub...${NC}"
git push origin $BRANCH

# Connexion SSH et déploiement
echo -e "${YELLOW}[2/5] Connexion au serveur...${NC}"

if [ "$1" == "quick" ]; then
    # Déploiement rapide (git pull + cache)
    ssh -p $SSH_PORT $SSH_USER@$SSH_HOST << EOF
        cd $REMOTE_PATH
        echo "Pull des modifications..."
        git pull origin $BRANCH

        echo "Nettoyage du cache..."
        php artisan cache:clear
        php artisan config:clear
        php artisan route:clear
        php artisan view:clear

        echo "Terminé!"
EOF
else
    # Déploiement complet
    ssh -p $SSH_PORT $SSH_USER@$SSH_HOST << EOF
        cd $REMOTE_PATH

        echo "[2/5] Pull des modifications..."
        git pull origin $BRANCH

        echo "[3/5] Installation des dépendances..."
        composer install --no-dev --optimize-autoloader --no-interaction

        echo "[4/5] Migrations de base de données..."
        php artisan migrate --force

        echo "[5/5] Optimisation pour la production..."
        php artisan cache:clear
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache

        echo "Terminé!"
EOF
fi

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  Déploiement terminé avec succès!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo "Site: https://faktur.lu"
