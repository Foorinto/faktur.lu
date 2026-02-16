#!/bin/bash

#############################################
# Script de déploiement faktur.lu
# Usage: ./deploy.sh [quick]
#############################################

set -e

# Configuration
SSH_USER="sc1beal9117"
SSH_HOST="saut.o2switch.net"
SSH_PORT="22"
REMOTE_PATH="/home2/sc1beal9117/faktur.lu"
BRANCH="main"
SITE_URL="https://faktur.lu"

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  Déploiement faktur.lu${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

# Vérifier la branche
CURRENT_BRANCH=$(git branch --show-current)
if [ "$CURRENT_BRANCH" != "$BRANCH" ]; then
    echo -e "${YELLOW}Attention: Vous êtes sur '$CURRENT_BRANCH', pas '$BRANCH'${NC}"
    read -p "Continuer? (y/n) " -n 1 -r
    echo
    [[ ! $REPLY =~ ^[Yy]$ ]] && exit 1
fi

# Vérifier les modifications
if [ -n "$(git status --porcelain)" ]; then
    echo -e "${RED}Erreur: Modifications non commitées${NC}"
    git status --short
    exit 1
fi

# Vérifier si des fichiers Vue/JS ont été modifiés depuis le dernier build
LAST_BUILD_TIME=$(stat -f %m public/build/manifest.json 2>/dev/null || echo "0")
LATEST_VUE_TIME=$(find resources/js -name "*.vue" -o -name "*.js" -o -name "*.ts" 2>/dev/null | xargs stat -f %m 2>/dev/null | sort -rn | head -1 || echo "0")

if [ "$LATEST_VUE_TIME" -gt "$LAST_BUILD_TIME" ]; then
    echo -e "${YELLOW}⚠️  Des fichiers Vue/JS ont été modifiés depuis le dernier build${NC}"
    echo ""
    read -p "Lancer 'npm run build' maintenant? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo -e "${YELLOW}Build des assets...${NC}"
        npm run build
        echo ""
        echo -e "${YELLOW}N'oubliez pas de commiter les changements du build:${NC}"
        echo "  git add public/build/ && git commit -m 'build: recompile assets' && git push"
        echo ""
        exit 0
    else
        echo -e "${YELLOW}Continuer sans rebuild? Les changements JS/Vue ne seront pas déployés.${NC}"
        read -p "Continuer quand même? (y/n) " -n 1 -r
        echo
        [[ ! $REPLY =~ ^[Yy]$ ]] && exit 1
    fi
fi

# Push
echo -e "${YELLOW}[1/4] Push vers GitHub...${NC}"
git push origin $BRANCH

# Déploiement SSH
echo -e "${YELLOW}[2/4] Connexion SSH...${NC}"
echo ""

if [ "$1" == "quick" ]; then
    ssh -t -p $SSH_PORT $SSH_USER@$SSH_HOST "cd $REMOTE_PATH && php artisan down --retry=30 || true && rm -f bootstrap/cache/*.php && php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear && git pull origin main && php artisan up && echo '=== Déploiement rapide terminé ==='"
else
    ssh -t -p $SSH_PORT $SSH_USER@$SSH_HOST "cd $REMOTE_PATH && php artisan down --retry=30 || true && rm -f bootstrap/cache/*.php && rm -rf storage/framework/cache/data/* && php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear && git pull origin main && composer install --no-dev --optimize-autoloader --no-interaction && php artisan migrate --force && php artisan route:cache && php artisan view:cache && php artisan config:clear && php artisan up && echo '=== Déploiement complet terminé ==='"
fi

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  Déploiement terminé!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo "Site: $SITE_URL"
