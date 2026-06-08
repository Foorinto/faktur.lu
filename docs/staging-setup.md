# Setup d'un environnement de staging sur o2switch

Guide pas-à-pas pour mettre en place `staging.faktur.lu` — environnement de test isolé qui pointe vers une **base de données séparée**, **n'envoie aucun email réel**, et permet de tester sans toucher à la prod.

> Temps total estimé : **45 minutes à 1h30** selon ton aisance avec cPanel et SSH.
> Coût : **0 €** (o2switch autorise plusieurs sous-domaines + plusieurs DB MySQL).

---

## Vue d'ensemble

| Composant | Production | Staging |
|---|---|---|
| Domaine | `faktur.lu` | `staging.faktur.lu` |
| Document root | `/home2/sc1beal9117/faktur.lu/public` | `/home2/sc1beal9117/faktur.lu-staging/public` |
| Base de données MySQL | `sc1beal9117_faktur` | `sc1beal9117_faktur_staging` |
| Mailer | Brevo (envois réels) | `log` (les emails sont écrits dans `storage/logs/laravel.log`, jamais envoyés) |
| Peppol | Réel | Désactivé |
| Visiteurs externes | Tout le monde | Basic auth + `noindex` |
| Branche déployée | `main` | `main` (ou `staging` si tu veux une branche dédiée) |
| Données | Vraies factures clients | `DemoDataSeeder` |

---

## Étape 1 — Créer le sous-domaine sur o2switch (5 min)

1. Connecte-toi à **cPanel** o2switch.
2. *Domaines → Sous-domaines.*
3. Crée le sous-domaine : `staging.faktur.lu`.
4. **Document root** : `/home2/sc1beal9117/faktur.lu-staging/public`
   - cPanel proposera par défaut `staging.faktur.lu/` — **modifie-le** pour inclure `/public` sinon Laravel ne servira pas correctement.

À la fin de cette étape, `https://staging.faktur.lu` doit déjà répondre (404 ou page vide, c'est normal — il n'y a pas encore de code).

---

## Étape 2 — Créer la base de données MySQL (5 min)

1. cPanel → *Bases de données MySQL*.
2. Crée la base : `sc1beal9117_faktur_staging`.
3. Crée un user MySQL : `sc1beal9117_fakstg` (ou autre nom court).
4. Génère un mot de passe fort, **note-le quelque part**.
5. Attribue ce user à la base avec **TOUS les privilèges** (ALL PRIVILEGES).

---

## Étape 3 — Cloner le repo en SSH (5 min)

Depuis ton Mac :

```bash
ssh sc1beal9117@saut.o2switch.net
```

Puis sur le serveur :

```bash
cd /home2/sc1beal9117
git clone git@github.com:Foorinto/faktur.lu.git faktur.lu-staging
cd faktur.lu-staging
```

> Si la clé SSH GitHub n'est pas configurée sur o2switch, clone via HTTPS :
> `git clone https://github.com/Foorinto/faktur.lu.git faktur.lu-staging`

---

## Étape 4 — Configurer le `.env` staging (10 min)

Toujours sur le serveur, dans `faktur.lu-staging` :

```bash
cp .env.example .env
nano .env
```

Modifie ces variables (les autres restent par défaut) :

```env
APP_NAME="faktur.lu STAGING"
APP_ENV=staging
APP_DEBUG=true
APP_URL=https://staging.faktur.lu

LOG_CHANNEL=daily

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=sc1beal9117_faktur_staging
DB_USERNAME=sc1beal9117_fakstg
DB_PASSWORD=<mot_de_passe_note_a_l_etape_2>

# CRUCIAL : aucun mail réel ne part en staging
MAIL_MAILER=log
MAIL_FROM_ADDRESS="staging@faktur.lu"
MAIL_FROM_NAME="faktur.lu STAGING"

QUEUE_CONNECTION=sync
SESSION_DRIVER=database
SESSION_LIFETIME=120

# Désactive Peppol pour ne pas envoyer de fausses factures sur le réseau
PEPPOL_ENABLED=false

# Désactive Stripe (clés de TEST si tu veux tester les paiements)
STRIPE_KEY=
STRIPE_SECRET=
```

Sauvegarde (`Ctrl+O`, `Ctrl+X`).

---

## Étape 5 — Installer les dépendances + générer la clé (5 min)

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan storage:link
chmod -R 775 storage bootstrap/cache
```

---

## Étape 6 — Migrer la base + seeder démo (5 min)

```bash
php artisan migrate --force
php artisan db:seed --class=PlansSeeder --force
php artisan db:seed --class=DemoDataSeeder --force
```

Le seeder doit afficher les credentials des 4 comptes démo. Note-les.

---

## Étape 7 — Build des assets (3 min)

Si o2switch a `npm` (ce qui n'est pas garanti sur du mutualisé) :

```bash
npm ci
npm run build
```

Sinon **build en local sur ton Mac et upload** :

```bash
# Sur ton Mac dans le repo principal
npm run build
# Puis upload le dossier public/build vers staging via scp
scp -r public/build sc1beal9117@saut.o2switch.net:/home2/sc1beal9117/faktur.lu-staging/public/
```

---

## Étape 8 — Protéger la staging (5 min)

Pour éviter que Google indexe la staging et que le grand public la découvre :

### 8a — Basic auth via `.htaccess`

Dans `public/.htaccess` (ajoute en haut, avant les autres règles) :

```apache
AuthType Basic
AuthName "Staging - Accès restreint"
AuthUserFile /home2/sc1beal9117/faktur.lu-staging/.htpasswd
Require valid-user
```

Crée le fichier de mot de passe :

```bash
htpasswd -c /home2/sc1beal9117/faktur.lu-staging/.htpasswd alex
# Tape le mot de passe que tu donneras à ton ami
```

### 8b — Bloquer l'indexation Google

Dans `public/robots.txt` (sur staging uniquement) :

```
User-agent: *
Disallow: /
```

Ou via une variable d'env qu'on lit dans la vue robots — mais le `.htaccess` basic auth couvre déjà ça.

---

## Étape 9 — Test final (5 min)

Visite `https://staging.faktur.lu`. Tu dois voir :
1. La popup d'authentification basique (rentre `alex` + mot de passe choisi).
2. La landing page de faktur.lu.
3. Tu peux te connecter avec `demo-owner@faktur.lu` / `Demo2026!`.
4. Le tableau de bord affiche 14 factures + 5 devis + 13 dépenses + 1 employé + 1 collaborateur.

Si oui : **staging opérationnelle**. Tu peux donner les credentials demo + le basic auth à ton ami fiduciaire.

---

## Étape 10 — Adapter `deploy.sh` pour déployer sur staging (10 min)

Crée `deploy-staging.sh` à la racine du repo (copie de `deploy.sh` avec ces différences) :

```bash
# Lignes à modifier dans deploy-staging.sh
REMOTE_PATH="/home2/sc1beal9117/faktur.lu-staging"
SITE_URL="https://staging.faktur.lu"
BRANCH="main"  # ou "staging" si tu crées une branche dédiée

# Et remplace l'appel à DemoDataSeeder dans la section seeders pour qu'il se rejoue à chaque deploy :
# Après les autres seeders, ajoute :
# php artisan db:seed --class=DemoDataSeeder --force &&
```

Comme ça à chaque `./deploy-staging.sh`, tu :
- Pull les derniers commits
- Migres la DB staging
- **Réinitialises les données démo** (utile : si ton ami a saisi des factures pendant son test, elles disparaissent et tu repars propre)

---

## Workflow type une fois la staging en place

```
# Pendant le dev
git add .
git commit -m "feat: nouvelle fonctionnalité"
git push

# Test sur staging d'abord
./deploy-staging.sh

# Vérifications manuelles sur https://staging.faktur.lu
# Si OK → deploy prod
./deploy.sh
```

Tu peux aussi laisser ton ami fiduciaire **tester de façon récurrente** sur staging sans jamais risquer la prod.

---

## Sécurité — checklist finale

- [ ] Basic auth activée sur staging (sinon ton ami trouvera le portail accountant en clair sur internet)
- [ ] `MAIL_MAILER=log` confirmé (vérifie : `php artisan tinker → Mail::raw('test', fn($m) => $m->to('test@test.com'))` ne doit RIEN envoyer)
- [ ] `PEPPOL_ENABLED=false`
- [ ] DB staging ≠ DB prod (vérifie : sur staging, `php artisan tinker → User::count()` doit retourner 3, pas le vrai chiffre prod)
- [ ] `robots.txt` Disallow ou basic auth (anti-indexation Google)
- [ ] Clés Stripe vidées ou en mode TEST uniquement

---

## Comment partager avec ton ami fiduciaire

Message type :

> Salut [Prénom], voici l'accès au staging :
>
> **URL** : https://staging.faktur.lu
> **Basic auth** : `alex` / `<mot_de_passe_htpasswd>`
>
> Une fois sur le site, tu peux te connecter avec :
>
> - **Propriétaire (vue principale)** : `demo-owner@faktur.lu` / `Demo2026!`
> - **Comptable (portail fiduciaire)** : `demo-accountant@faktur.lu` / `Demo2026!` — URL `/comptable/login`
>
> Tu peux casser, supprimer, modifier tout ce que tu veux — c'est une base de test, je remets tout à zéro à chaque fois.
>
> Aucun email réel n'est envoyé depuis cet environnement, donc n'aie pas peur si tu cliques sur « Envoyer la facture » ou « Inviter un comptable ».

---

## Coût en maintenance

Une fois la staging en place :
- **Par mois** : zéro action requise
- **Par déploiement** : 1 commande supplémentaire (`./deploy-staging.sh`) — environ 1 minute
- **Quand le seeder évolue** (nouveaux modèles à seeder) : 5-15 min ponctuelles pour étendre `DemoDataSeeder.php`
