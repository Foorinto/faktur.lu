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
REMOTE_PATH="/home2/sc1beal9117/faktur.lu-staging"
BRANCH="main"
SITE_URL="https://staging.faktur.lu"
BACKUP_DIR="/home2/sc1beal9117/backups-staging"

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  Déploiement faktur.lu — STAGING${NC}"
echo -e "${BLUE}  ${SITE_URL}${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# ---------------------------------------------------------------------------
# Prerendering (SEO / robots) : génère les snapshots HTML des pages publiques
# en pilotant Chrome local CONTRE LA PROD déjà déployée (données réelles), puis
# les transfère par rsync vers le serveur. Servis aux robots uniquement par le
# middleware ServePrerendered ; transparent pour les humains.
#
# Ne fait JAMAIS échouer le déploiement : le site fonctionne sans snapshots
# (le middleware fait un pass-through si un fichier manque). Toute erreur ici
# est un simple avertissement.
# ---------------------------------------------------------------------------
prerender_and_sync() {
    echo ""
    echo -e "${YELLOW}[Prerendering] Génération des snapshots publics (robots)...${NC}"
    set +e
    local PORT=8129
    # Génère contre une instance LOCALE : o2switch/LiteSpeed rate-limite la rafale
    # d'assets du headless (HTTP 429) → le SPA ne se monte pas. En local, pas de
    # rate-limit. APP_URL=$SITE_URL → canonical/hreflang/og corrects ; les liens
    # internes sont relatifs et les scripts sont strippés. Pages marketing identiques
    # à la prod (contenu statique).
    rm -f public/hot
    APP_URL="$SITE_URL" php artisan serve --port=$PORT > /tmp/faktur-prerender-serve.log 2>&1 &
    local SERVE_PID=$!
    # Attendre que le serveur local réponde (jusqu'à 60s : sur une machine chargée en
    # fin de déploiement, le 1er boot Laravel peut être lent).
    local tries=0
    until curl -s -o /dev/null "http://127.0.0.1:$PORT/fr"; do
        tries=$((tries + 1))
        if [ $tries -ge 120 ]; then
            echo -e "${RED}⚠️  Serveur local de prerendering non démarré (port $PORT). Snapshots NON régénérés.${NC}"
            echo -e "${YELLOW}   Voir /tmp/faktur-prerender-serve.log. Le site reste OK.${NC}"
            kill $SERVE_PID 2>/dev/null
            set -e
            return 0
        fi
        sleep 0.5
    done
    echo -e "${BLUE}   Serveur local prêt, génération...${NC}"
    BASE_URL="http://127.0.0.1:$PORT" PRERENDER_CONCURRENCY=4 npm run prerender
    local gen_rc=$?
    kill $SERVE_PID 2>/dev/null
    if [ $gen_rc -ne 0 ]; then
        echo -e "${RED}⚠️  Génération des snapshots INCOMPLÈTE (code $gen_rc). Snapshots NON synchronisés.${NC}"
        echo -e "${YELLOW}   Le site reste OK. Relance: ./deploy-staging.sh prerender-only${NC}"
        set -e
        return 0
    fi
    echo -e "${YELLOW}[Prerendering] Transfert rsync vers le serveur...${NC}"
    rsync -az --delete -e "ssh -p $SSH_PORT" \
        public/prerendered/ "$SSH_USER@$SSH_HOST:$REMOTE_PATH/public/prerendered/"
    if [ $? -ne 0 ]; then
        echo -e "${YELLOW}⚠️  rsync échoué. Le site reste OK ; snapshots non synchronisés.${NC}"
    else
        echo -e "${GREEN}✓ Snapshots synchronisés vers le serveur${NC}"
    fi
    set -e
    return 0
}

# Mode dédié : régénère + synchronise les snapshots SANS redéployer le code.
# Utile quand seul le contenu a changé (nouvel article de blog publié, etc.).
if [ "$1" == "prerender-only" ]; then
    echo -e "${BLUE}Mode prerender-only : régénération + sync des snapshots (sans déploiement)${NC}"
    prerender_and_sync
    echo -e "${GREEN}Terminé.${NC}"
    exit 0
fi

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

# Vérifier les migrations en attente
echo -e "${BLUE}Vérification des migrations...${NC}"
PENDING=$(php artisan migrate:status 2>/dev/null | grep "No" | head -5 || true)
if [ -n "$PENDING" ]; then
    echo -e "${YELLOW}⚠️  Migrations en attente:${NC}"
    echo "$PENDING"
    echo ""
fi

# Push
echo -e "${YELLOW}[1/5] Push vers GitHub...${NC}"
git push origin $BRANCH

# Déploiement SSH
echo -e "${YELLOW}[2/5] Connexion SSH et sauvegarde BDD...${NC}"
echo ""

# Commande de backup BDD (exécutée en premier, avant toute modification)
BACKUP_CMD="
mkdir -p $BACKUP_DIR &&
TIMESTAMP=\$(date +%Y%m%d_%H%M%S) &&
echo '--- Sauvegarde de la base de données ---' &&
cd $REMOTE_PATH &&
DB_NAME=\$(grep DB_DATABASE .env | head -1 | cut -d '=' -f 2) &&
DB_USER=\$(grep DB_USERNAME .env | head -1 | cut -d '=' -f 2) &&
DB_PASS=\$(grep DB_PASSWORD .env | head -1 | cut -d '=' -f 2) &&
mysqldump -u\$DB_USER -p\$DB_PASS \$DB_NAME > $BACKUP_DIR/backup_\${TIMESTAMP}.sql &&
echo \"Backup sauvegardé: $BACKUP_DIR/backup_\${TIMESTAMP}.sql\" &&
echo \"Taille: \$(du -h $BACKUP_DIR/backup_\${TIMESTAMP}.sql | cut -f1)\" &&
echo ''
"

# Commande de déploiement avec gestion d'erreur
# Modes:
#   ./deploy.sh                    -> mode complet (par défaut)
#   ./deploy.sh quick              -> mode rapide (sans composer/migrations)
#   ./deploy.sh content-fix        -> mode complet + RandomizeBlogDates (one-shot, randomise les dates)
# Safety net : tout deploiement passe par un wrapper qui garantit `php artisan up`
# en cas d'echec, pour ne JAMAIS laisser le site bloque en mode maintenance.
# La presence d'un trap EXIT bash assure que `up` est rejoue meme si une etape echoue.
SAFETY_WRAPPER_OPEN="
cd $REMOTE_PATH &&
trap 'echo \"[SAFETY NET] forcing php artisan up\" ; php artisan up || true' EXIT &&
"

# Stash automatique des modifs locales (.htaccess modifie par o2switch via cPanel,
# logs, etc.) pour eviter que git pull echoue sur des fichiers modifies cote serveur.
# Le stash sera applique a la fin si le pull a reussi ; sinon on l'ignore.
# IMPORTANT : la chaine doit se terminer sur une commande qui retourne 0,
# sans newline final, pour que le `&&` qui suit dans DEPLOY_CMD reste valide.
GIT_PULL_SAFE="echo '--- Stash des modifs locales eventuelles (htaccess auto-modifie par cPanel...) ---' && (git stash push --include-untracked -m \"deploy-auto-stash-\$(date +%s)\" || true) && git pull origin main && echo '--- Tentative de re-application du stash (peut echouer si conflits) ---' && (git stash pop || git stash drop || true)"

if [ "$1" == "quick" ]; then
    DEPLOY_CMD="
${SAFETY_WRAPPER_OPEN}
echo '--- Mode rapide ---' &&
echo '[3/5] Maintenance mode...' &&
php artisan down --retry=30 || true &&
echo '[4/5] Pull et cache...' &&
rm -f bootstrap/cache/*.php &&
php artisan cache:clear &&
php artisan config:clear &&
php artisan route:clear &&
php artisan view:clear &&
${GIT_PULL_SAFE} &&
php artisan config:clear &&
echo '[5/5] Remise en ligne...' &&
php artisan up &&
echo '=== Déploiement rapide terminé ==='
"
else
    # Seeder de randomisation des dates: uniquement en mode content-fix (one-shot)
    # IMPORTANT: la commande doit toujours se terminer par && pour ne pas casser
    # le chainage. Quand vide, on utilise `:` (no-op bash) pour rester valide.
    if [ "$1" == "content-fix" ]; then
        EXTRA_SEEDER="echo '--- One-shot: randomisation des dates blog (cohérent par translation_key) ---' && php artisan db:seed --class=RandomizeBlogDatesFeb2026Seeder --force &&"
    else
        EXTRA_SEEDER=": &&"
    fi

    DEPLOY_CMD="
${SAFETY_WRAPPER_OPEN}
echo '--- Mode complet ---' &&
echo '[3/5] Maintenance mode...' &&
php artisan down --retry=30 || true &&
echo '[4/5] Pull, dépendances et migrations...' &&
rm -f bootstrap/cache/*.php &&
rm -rf storage/framework/cache/data/* &&
php artisan cache:clear &&
php artisan config:clear &&
php artisan route:clear &&
php artisan view:clear &&
${GIT_PULL_SAFE} &&
composer install --no-dev --optimize-autoloader --no-interaction &&
echo '--- Migrations ---' &&
php artisan migrate --force &&
echo '--- Seeders idempotents (data fixes & content updates) ---' &&
php artisan db:seed --class=PlansSeeder --force &&
php artisan db:seed --class=BlogPostsPortugueseSeeder --force &&
php artisan db:seed --class=UpdateBlog2025To2026SlugsSeeder --force &&
php artisan db:seed --class=UpdateBlogContentFixesSeeder --force &&
php artisan db:seed --class=ReplaceEmDashesSeeder --force &&
php artisan db:seed --class=BlogScheduledSummer2026Seeder --force &&
php artisan db:seed --class=BlogScheduledSummer2026DeSeeder --force &&
php artisan db:seed --class=BlogScheduledSummer2026EnSeeder --force &&
php artisan db:seed --class=BlogScheduledSummer2026LbSeeder --force &&
php artisan db:seed --class=BlogScheduledSummer2026PtSeeder --force &&
${EXTRA_SEEDER}
echo '--- Cache ---' &&
php artisan route:cache &&
php artisan view:cache &&
php artisan config:clear &&
echo '[5/5] Remise en ligne...' &&
php artisan up &&
echo '=== Déploiement complet terminé ==='
"
fi

# Exécution : backup puis déploiement
ssh -t -p $SSH_PORT $SSH_USER@$SSH_HOST "$BACKUP_CMD $DEPLOY_CMD"
DEPLOY_EXIT=$?

if [ $DEPLOY_EXIT -ne 0 ]; then
    echo -e "${RED}!!! ERREUR durant le déploiement !!!${NC}"
    echo "Tentative de remise en ligne du site..."
    ssh -p $SSH_PORT $SSH_USER@$SSH_HOST "cd $REMOTE_PATH && php artisan up 2>/dev/null || true"
    echo -e "Le site est remis en ligne. La base de données n'a PAS été modifiée si la migration a échoué."
    echo -e "Un backup est disponible dans: ${BLUE}$BACKUP_DIR/${NC}"
    exit 1
fi

# Vérification post-déploiement
echo ""
echo -e "${YELLOW}Vérification du site...${NC}"
# -L : suit la redirection racine (/ → /fr) sinon HTTP_STATUS vaut 302 et la condition
# "== 200" plus bas est toujours fausse (ce qui sautait silencieusement le prerender).
HTTP_STATUS=$(curl -sL -o /dev/null -w "%{http_code}" "$SITE_URL" 2>/dev/null || echo "000")

if [ "$HTTP_STATUS" == "200" ]; then
    echo -e "${GREEN}✓ Site accessible (HTTP $HTTP_STATUS)${NC}"
else
    echo -e "${RED}⚠️  Site retourne HTTP $HTTP_STATUS — vérifiez manuellement: $SITE_URL${NC}"
fi

# Prerendering post-déploiement : régénère les snapshots contre la prod fraîchement
# déployée (sauf en mode rapide). N'impacte pas le statut du déploiement.
if [ "$1" != "quick" ] && [ "$HTTP_STATUS" == "200" ]; then
    prerender_and_sync
fi

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  Déploiement terminé!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo "Site: $SITE_URL"
echo -e "Backup: ${BLUE}$BACKUP_DIR/${NC}"
echo ""
echo -e "${YELLOW}Pour restaurer en cas de problème:${NC}"
echo "  ssh $SSH_USER@$SSH_HOST"
echo "  mysql -u\$DB_USER -p \$DB_NAME < $BACKUP_DIR/backup_XXXXXXXX.sql"
echo ""
