# Guide de Déploiement faktur.lu sur o2switch

## Prérequis

- Accès cPanel o2switch
- Domaine/sous-domaine configuré : `faktur.lu`
- PHP 8.4 activé

---

## Étape 1 : Créer la base de données MySQL

1. Connectez-vous à cPanel o2switch
2. Allez dans **Bases de données MySQL**
3. Créez une nouvelle base de données (ex: `foorinto_fakturlu`)
4. Créez un utilisateur MySQL (ex: `foorinto_fakturlu_user`)
5. Associez l'utilisateur à la base avec **TOUS LES PRIVILÈGES**
6. Notez les informations :
   - Nom de la base : `foorinto_fakturlu`
   - Utilisateur : `foorinto_fakturlu_user`
   - Mot de passe : `votre_mot_de_passe`

---

## Étape 2 : Configurer le sous-domaine

1. Dans cPanel, allez dans **Sous-domaines** ou **Domaines**
2. Vérifiez que `faktur.lu` pointe vers le bon dossier
3. **Important** : Le document root doit pointer vers le dossier où vous uploadez l'application

---

## Étape 3 : Préparer les fichiers

### Option A : Upload via Gestionnaire de fichiers cPanel

1. Créez une archive ZIP de tout le projet (sauf `node_modules`)
2. Uploadez le ZIP dans le dossier du sous-domaine
3. Extrayez l'archive

### Option B : Upload via FTP

1. Utilisez FileZilla ou autre client FTP
2. Connectez-vous avec vos identifiants FTP o2switch
3. Uploadez TOUS les fichiers dans le dossier du sous-domaine

### Fichiers à NE PAS uploader :
- `node_modules/`
- `.git/`
- `.env` (vous créerez le .env production)

---

## Étape 4 : Configurer l'environnement

1. Renommez `.env.production` en `.env`
2. Éditez `.env` avec vos vraies informations :

```env
APP_NAME=faktur.lu
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_TIMEZONE=Europe/Luxembourg
APP_URL=https://faktur.lu

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=foorinto_fakturlu
DB_USERNAME=foorinto_fakturlu_user
DB_PASSWORD=VOTRE_VRAI_MOT_DE_PASSE

SESSION_DRIVER=database
```

---

## Étape 5 : Configurer les permissions

Dans cPanel > Gestionnaire de fichiers, définissez les permissions :

| Dossier | Permission |
|---------|------------|
| `storage/` | 775 (récursif) |
| `bootstrap/cache/` | 775 |

---

## Étape 6 : Exécuter le déploiement

1. Éditez `deploy.php` et changez la clé secrète :
   ```php
   $secretKey = 'VOTRE_CLE_SECRETE_UNIQUE';
   ```

2. Accédez aux URLs suivantes dans l'ordre :

   ```
   https://faktur.lu/deploy.php?key=VOTRE_CLE&action=key:generate
   ```
   → Génère la clé APP_KEY

   ```
   https://faktur.lu/deploy.php?key=VOTRE_CLE&action=storage:link
   ```
   → Crée le lien symbolique storage

   ```
   https://faktur.lu/deploy.php?key=VOTRE_CLE&action=migrate
   ```
   → Crée les tables en base de données

   ```
   https://faktur.lu/deploy.php?key=VOTRE_CLE&action=optimize
   ```
   → Optimise l'application

3. **IMPORTANT** : Supprimez `deploy.php` après utilisation !

---

## Étape 7 : Vérification

1. Accédez à `https://faktur.lu`
2. Vous devriez voir la page de connexion
3. Créez un compte utilisateur
4. Testez les fonctionnalités

---

## Dépannage

### Erreur 500
- Vérifiez les permissions de `storage/` et `bootstrap/cache/`
- Vérifiez que `.env` existe et est correctement configuré
- Consultez `storage/logs/laravel.log`

### Page blanche
- Activez temporairement `APP_DEBUG=true` dans `.env`
- Vérifiez la version PHP (8.3+ requis)

### Erreur de connexion DB
- Vérifiez les identifiants dans `.env`
- Vérifiez que l'utilisateur a les privilèges sur la base

### Assets non chargés (CSS/JS)
- Vérifiez que le dossier `public/build/` existe
- Vérifiez l'URL dans `.env` (avec https)

### Lien storage ne fonctionne pas
- Certains hébergeurs ne supportent pas les liens symboliques
- Alternative : copiez manuellement `storage/app/public` vers `public/storage`

---

## Structure des fichiers sur o2switch

```
/home/foorinto/faktur.lu/
├── .env                    ← Configuration production
├── .htaccess               ← Redirection vers public
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
│   ├── .htaccess
│   ├── index.php
│   ├── build/              ← Assets compilés
│   └── storage/            ← Lien symbolique
├── resources/
├── routes/
├── storage/
│   ├── app/
│   ├── framework/
│   └── logs/
└── vendor/
```

---

## Mises à jour futures

Pour mettre à jour l'application :

1. Uploadez les nouveaux fichiers (sauf `.env`, `storage/`, `vendor/`)
2. Accédez à `deploy.php?action=migrate` si nouvelles migrations
3. Accédez à `deploy.php?action=cache:clear` pour vider les caches
4. Supprimez `deploy.php`

---

## Sécurité

- [ ] Supprimez `deploy.php` après le déploiement
- [ ] Vérifiez que `APP_DEBUG=false`
- [ ] Vérifiez que `.env` n'est pas accessible publiquement
- [ ] Activez HTTPS (certificat Let's Encrypt via cPanel)
