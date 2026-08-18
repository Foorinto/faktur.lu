#!/usr/bin/env bash
#
# Vérifie ce que les robots reçoivent réellement en production.
#
# Le site peut être parfait pour un humain et cassé pour Googlebot : le SPA et
# les snapshots pré-rendus sont deux rendus distincts, et seul le second est
# indexé. Les liens vers 127.0.0.1 ont vécu des mois pour cette raison.
#
#   ./scripts/verifier-front.sh                    # production
#   ./scripts/verifier-front.sh https://staging.faktur.lu
#
set -u

SITE="${1:-https://faktur.lu}"
UA_BOT="Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)"
V="\033[0;32m"; R="\033[0;31m"; J="\033[1;33m"; N="\033[0m"
ECHECS=0

verifier() { # libellé, valeur observée, valeur attendue (sous-chaîne)
    if [[ "$2" == *"$3"* ]]; then
        printf "  ${V}✓${N} %-46s %s\n" "$1" "${2:0:46}"
    else
        printf "  ${R}✗${N} %-46s %s\n" "$1" "${2:0:46}"
        ECHECS=$((ECHECS + 1))
    fi
}

echo -e "${J}Ce que les robots reçoivent sur ${SITE}${N}"
echo

PAGE=$(curl -sS --max-time 25 -A "$UA_BOT" "$SITE/fr" -D /tmp/vf.h 2>/dev/null)

# 1. Le snapshot est-il bien servi ?
verifier "snapshot pré-rendu servi" "$(grep -ci 'x-prerendered' /tmp/vf.h)" "1"

# 2. Aucun lien ne doit pointer vers l'hôte de génération.
SANS_SCRIPTS=$(printf '%s' "$PAGE" | perl -0777 -pe 's/<script[^>]*>.*?<\/script>//gs')
FUITES=$(printf '%s' "$SANS_SCRIPTS" | grep -o 'href="[^"]*127\.0\.0\.1[^"]*"' | wc -l | tr -d ' ')
verifier "liens vers 127.0.0.1 (attendu 0)" "$FUITES" "0"

# 3. Le contenu est-il là, et le titre rempli ?
verifier "titre de page" "$(printf '%s' "$PAGE" | grep -oE '<title[^>]*>[^<]*' | head -1 | sed 's/.*>//')" "faktur.lu"
verifier "un h1 est présent" "$(printf '%s' "$PAGE" | grep -c '<h1')" "1"

# 4. Identité légale, pour les moteurs comme pour les modèles.
verifier "numéro de TVA dans schema.org" "$(printf '%s' "$PAGE" | grep -o 'LU37176916' | head -1)" "LU37176916"

# 5. Catégorie de blog traduite (elle portait le nom français en 5 langues).
TITRE_DE=$(curl -sS --max-time 25 -A "$UA_BOT" "$SITE/de/blog/kategorie/reglementation" 2>/dev/null | grep -oE '<title[^>]*>[^<]*' | head -1 | sed 's/.*>//')
verifier "catégorie traduite en allemand" "$TITRE_DE" "Regelwerk"

# 6. Les pages de tags sortent de l'index.
ROBOTS=$(curl -sS --max-time 25 -A "$UA_BOT" "$SITE/fr/blog/tag/tva" 2>/dev/null | grep -o 'content="noindex[^"]*"' | head -1)
verifier "tags hors index" "$ROBOTS" "noindex"

# 7. Clé IndexNow servie en clair.
verifier "clé IndexNow publiée" "$(curl -sS --max-time 15 "$SITE/405e199542218dc4594cd94238a0123e.txt" 2>/dev/null)" "405e1995"

echo
if [ "$ECHECS" -eq 0 ]; then
    echo -e "  ${V}Tout est conforme.${N}"
else
    echo -e "  ${R}${ECHECS} vérification(s) en échec.${N}"
fi
exit "$ECHECS"
