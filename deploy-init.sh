#!/bin/bash

#############################################
# Initialisation du dépôt Git sur le serveur
# À exécuter UNE SEULE FOIS
#############################################

set -e

# Configuration (même que deploy.sh)
SSH_USER="sc1beal9117"
SSH_HOST="saut.o2switch.net"
SSH_PORT="22"
REMOTE_PATH="/home2/sc1beal9117/faktur.lu"
GIT_REPO="git@github.com:VOTRE_USERNAME/Facturation-luxembourg.git"  # À ajuster

echo "=========================================="
echo "  Initialisation Git sur o2switch"
echo "=========================================="
echo ""

# Test de connexion SSH
echo "[1/3] Test de connexion SSH..."
ssh -p $SSH_PORT $SSH_USER@$SSH_HOST "echo 'Connexion SSH OK!'"

echo ""
echo "[2/3] Configuration Git sur le serveur..."
ssh -p $SSH_PORT $SSH_USER@$SSH_HOST << EOF
    # Configurer Git
    git config --global user.email "deploy@faktur.lu"
    git config --global user.name "Deploy"

    # Vérifier si le dossier existe
    if [ -d "$REMOTE_PATH" ]; then
        echo "Le dossier $REMOTE_PATH existe déjà"
        cd $REMOTE_PATH

        # Vérifier si c'est déjà un repo Git
        if [ -d ".git" ]; then
            echo "Dépôt Git déjà initialisé"
            git remote -v
        else
            echo "Initialisation du dépôt Git..."
            git init
            git remote add origin $GIT_REPO
            echo "Remote ajouté"
        fi
    else
        echo "Erreur: Le dossier $REMOTE_PATH n'existe pas"
        exit 1
    fi
EOF

echo ""
echo "[3/3] Instructions suivantes:"
echo ""
echo "1. Ajoutez une clé de déploiement GitHub:"
echo "   - Sur le serveur, générez une clé: ssh-keygen -t ed25519 -C 'o2switch-deploy'"
echo "   - Copiez la clé publique: cat ~/.ssh/id_ed25519.pub"
echo "   - Ajoutez-la dans GitHub → Settings → Deploy keys"
echo ""
echo "2. Puis exécutez ./deploy.sh pour le premier déploiement"
