#!/bin/bash
#
# Cleanup script SAFE pour faktur.lu en production o2switch.
#
# Philosophie : AUCUNE suppression directe. Tout est deplace dans un dossier
# de quarantaine (~/faktur-quarantine-TIMESTAMP). Si quelque chose casse,
# on peut tout restaurer en une commande (affichee a la fin).
#
# Categories nettoyees :
#   A. Fichiers parasites (q, sued_at, .env.bak)
#   B. Scripts PHP de debug exposes publiquement (FAILLE DE SECURITE)
#   C. Anciens assets de build Vite (non references dans manifest.json)
#
# Categories JAMAIS touchees (on ne sait pas si critiques) :
#   - index.php a la racine (peut etre lie a la config Apache o2switch)
#   - public/error_log (Apache le regenere automatiquement, utile en debug)
#   - storage/, bootstrap/cache/ (geres par Laravel)
#   - Tout ce qui est tracke par git
#
# Usage :
#   bash scripts/cleanup-server.sh --dry-run   # Liste ce qui serait deplace, sans rien faire
#   bash scripts/cleanup-server.sh             # Mode interactif, demande confirmation par categorie
#

set -e

DRY_RUN=false
[ "$1" = "--dry-run" ] && DRY_RUN=true

# ---- Couleurs ----
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# ---- Sanity checks ----
if [ ! -f "artisan" ]; then
    echo -e "${RED}ERREUR : ce script doit etre execute depuis la racine Laravel (fichier 'artisan' absent ici)${NC}"
    echo "Lance d'abord : cd /home2/sc1beal9117/faktur.lu"
    exit 1
fi

if [ ! -f "public/build/manifest.json" ]; then
    echo -e "${RED}ERREUR : public/build/manifest.json absent. Le projet n'a pas encore ete builde ?${NC}"
    exit 1
fi

# ---- Quarantaine ----
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
QUARANTINE="$HOME/faktur-quarantine-$TIMESTAMP"

if [ "$DRY_RUN" = false ]; then
    mkdir -p "$QUARANTINE"
    echo -e "${BLUE}Quarantaine : $QUARANTINE${NC}"
    echo ""
fi

# ---- Helpers ----
move_to_quarantine() {
    local src="$1"
    local rel_path="${src#./}"
    local dest="$QUARANTINE/$rel_path"

    if [ "$DRY_RUN" = true ]; then
        echo "  [DRY-RUN] $src"
        return
    fi

    mkdir -p "$(dirname "$dest")"
    mv "$src" "$dest"
    echo "  -> $rel_path"
}

confirm() {
    local prompt="$1"
    if [ "$DRY_RUN" = true ]; then
        return 0
    fi
    read -p "$prompt (y/N) " -n 1 -r
    echo
    [[ $REPLY =~ ^[Yy]$ ]]
}

# ============================================================
# CATEGORIE A : fichiers parasites evidents
# ============================================================
echo -e "${YELLOW}=== Categorie A : fichiers parasites evidents ===${NC}"

SPAM_FILES=("q" "sued_at" ".env.bak")
FOUND_A=()

for f in "${SPAM_FILES[@]}"; do
    if [ -e "$f" ]; then
        FOUND_A+=("$f")
        SIZE=$(du -h "$f" 2>/dev/null | cut -f1)
        echo "  $f ($SIZE)"
    fi
done

if [ ${#FOUND_A[@]} -eq 0 ]; then
    echo "  (aucun)"
else
    if confirm "Deplacer ces ${#FOUND_A[@]} fichiers vers la quarantaine ?"; then
        for f in "${FOUND_A[@]}"; do
            move_to_quarantine "$f"
        done
    else
        echo "  Skip categorie A"
    fi
fi
echo ""

# ============================================================
# CATEGORIE B : scripts PHP de debug exposes publiquement
# ============================================================
echo -e "${YELLOW}=== Categorie B : scripts PHP de debug en public/ (FAILLE DE SECURITE) ===${NC}"

DEBUG_SCRIPTS=("public/clear-config-cache.php" "public/debug-env.php" "public/fix-key.php")
FOUND_B=()

for f in "${DEBUG_SCRIPTS[@]}"; do
    if [ -e "$f" ]; then
        FOUND_B+=("$f")
        SIZE=$(du -h "$f" 2>/dev/null | cut -f1)
        echo "  $f ($SIZE)"
    fi
done

if [ ${#FOUND_B[@]} -eq 0 ]; then
    echo "  (aucun)"
else
    echo -e "${RED}  ATTENTION : ces scripts sont accessibles via https://faktur.lu/<nom>.php${NC}"
    echo -e "${RED}  Ils peuvent exposer ton .env, vider tes caches a distance, etc.${NC}"
    if confirm "Les deplacer vers la quarantaine ?"; then
        for f in "${FOUND_B[@]}"; do
            move_to_quarantine "$f"
        done
    else
        echo "  Skip categorie B (mais c'est une vraie faille de securite a corriger)"
    fi
fi
echo ""

# ============================================================
# CATEGORIE C : anciens assets de build Vite
# ============================================================
echo -e "${YELLOW}=== Categorie C : anciens assets de build Vite ===${NC}"

# Extraire la liste des assets actuellement references dans manifest.json
# On grep les valeurs de "file" et les entrees des arrays "css"
REFERENCED=$(grep -oE '"assets/[^"]+"' public/build/manifest.json | sed 's|^"assets/||;s|"$||' | sort -u)
REFERENCED_COUNT=$(echo "$REFERENCED" | wc -l)
echo "  Assets references dans manifest.json : $REFERENCED_COUNT"

ALL_COUNT=$(ls public/build/assets/ 2>/dev/null | wc -l)
echo "  Assets presents dans public/build/assets/ : $ALL_COUNT"

# Trouver les obsoletes
STALE=()
for asset in $(ls public/build/assets/); do
    if ! echo "$REFERENCED" | grep -qx "$asset"; then
        STALE+=("public/build/assets/$asset")
    fi
done

STALE_COUNT=${#STALE[@]}
echo "  Assets obsoletes a deplacer : $STALE_COUNT"

if [ $STALE_COUNT -eq 0 ]; then
    echo "  (rien a faire)"
else
    # Calculer la taille totale
    TOTAL_SIZE=$(du -ch "${STALE[@]}" 2>/dev/null | tail -1 | cut -f1)
    echo "  Taille totale : $TOTAL_SIZE"
    echo ""
    echo -e "${BLUE}  Risque : si un utilisateur a une page faktur.lu ouverte avec d'anciens chunks${NC}"
    echo -e "${BLUE}  references, sa prochaine action chargeant un nouveau chunk pourrait echouer.${NC}"
    echo -e "${BLUE}  Solution simple si ca arrive : refresh (Cmd+R / F5).${NC}"
    if confirm "Deplacer les $STALE_COUNT assets obsoletes vers la quarantaine ?"; then
        for asset in "${STALE[@]}"; do
            move_to_quarantine "$asset"
        done
    else
        echo "  Skip categorie C"
    fi
fi
echo ""

# ============================================================
# Categories non touchees (juste lister pour info)
# ============================================================
echo -e "${YELLOW}=== Fichiers signales mais NON touches (a inspecter manuellement) ===${NC}"

if [ -e "index.php" ]; then
    echo -e "  ${BLUE}index.php (a la racine)${NC} -> contenu suspect, a inspecter avec : cat index.php | head -20"
fi

if [ -e "public/error_log" ]; then
    SIZE=$(du -h public/error_log 2>/dev/null | cut -f1)
    echo "  public/error_log ($SIZE) -> regenere automatiquement par Apache, peut etre tronque manuellement avec : > public/error_log"
fi
echo ""

# ============================================================
# Resume
# ============================================================
if [ "$DRY_RUN" = true ]; then
    echo -e "${GREEN}=== Mode DRY-RUN : aucune modification effectuee ===${NC}"
    echo "Pour executer reellement : bash scripts/cleanup-server.sh"
else
    echo -e "${GREEN}=== Cleanup termine ===${NC}"
    echo ""
    echo -e "${BLUE}Tout est en quarantaine ici :${NC}"
    echo "  $QUARANTINE"
    echo ""
    echo -e "${YELLOW}Si quelque chose casse (peu probable), restaure tout avec :${NC}"
    echo "  cd $QUARANTINE && cp -rn ./. /home2/sc1beal9117/faktur.lu/ && cd -"
    echo ""
    echo -e "${YELLOW}Quand tu es sur que tout va bien (apres quelques jours d'usage normal), tu peux supprimer la quarantaine :${NC}"
    echo "  rm -rf $QUARANTINE"
fi
