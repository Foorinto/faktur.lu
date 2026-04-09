# Commandes Artisan - faktur.lu

Liste de toutes les commandes personnalisées disponibles dans l'application.

---

## Gestion des utilisateurs

### `user:grant-lifetime-pro`

Accorde un abonnement Pro illimite a vie a un utilisateur, sans passer par Stripe.

```bash
php artisan user:grant-lifetime-pro email@exemple.com
```

- Supprime les abonnements existants de l'utilisateur
- Cree un abonnement Pro local sans date d'expiration
- Aucune facturation Stripe associee
- L'utilisateur obtient toutes les fonctionnalites Pro sans limite

---

## Sauvegardes

### `backup:run`

Lance une sauvegarde de la base de donnees (dump MySQL, compression, chiffrement, upload cloud).

```bash
php artisan backup:run
php artisan backup:run --no-cloud       # Sans upload cloud (rclone)
php artisan backup:run --no-cleanup     # Sans nettoyage des anciens backups
```

- Cree un dump MySQL compresse
- Chiffre le fichier si configure
- Upload vers le cloud via rclone (optionnel)
- Nettoie les anciens backups au-dela de la retention
- Envoie un email de notification (succes ou echec)

### `backup:list`

Liste toutes les sauvegardes locales disponibles.

```bash
php artisan backup:list
```

- Affiche un tableau avec : nom du fichier, taille, date, statut de chiffrement

### `backup:restore`

Restaure la base de donnees depuis un fichier de sauvegarde.

```bash
php artisan backup:restore                      # Choix interactif
php artisan backup:restore chemin/vers/backup.sql.gz   # Fichier specifique
```

- Si aucun fichier n'est fourni, liste les backups disponibles et demande un choix
- Demande une confirmation explicite avant d'ecraser la base
- Supporte les fichiers compresses et chiffres

---

## Relances et notifications

### `reminders:send`

Envoie les emails de rappel pour les rappels CRM en attente.

```bash
php artisan reminders:send
```

- Cherche les rappels incomplets dont la date est passee
- Envoie un email a l'utilisateur avec le titre, le client, la description et la date
- A planifier en cron : `* * * * *` ou `0 * * * *`

### `trial:send-reminders`

Envoie les emails de rappel pour les periodes d'essai.

```bash
php artisan trial:send-reminders
```

- Envoie un email "essai se termine bientot" aux utilisateurs dont l'essai expire dans 3 jours
- Envoie un email "essai expire" aux utilisateurs dont l'essai expire aujourd'hui
- Passe le statut du compte en `expired` pour les essais termines sans abonnement
- A planifier en cron : `0 9 * * *` (une fois par jour)

### `reminders:test`

Teste l'envoi des relances de paiement pour les factures impayees.

```bash
php artisan reminders:test                        # Toutes les factures eligibles
php artisan reminders:test --user=1               # Pour un utilisateur specifique
php artisan reminders:test --invoice=42            # Pour une facture specifique
php artisan reminders:test --dry-run               # Simulation sans envoi
```

- Affiche les factures eligibles aux relances (en retard, finalisees/envoyees)
- Calcule le nombre de jours de retard et le niveau de relance
- `--dry-run` : montre ce qui serait envoye sans envoyer
- Demande confirmation avant l'envoi reel

---

## Maintenance

### `monitoring:cleanup`

Supprime les anciennes metriques de monitoring au-dela de la periode de retention.

```bash
php artisan monitoring:cleanup
```

- Nettoie les metriques de requetes obsoletes
- Affiche le nombre d'entrees supprimees
- A planifier en cron : `0 3 * * *` (une fois par jour, la nuit)

---

## Deploiement

### `deploy.sh`

Script de deploiement (pas une commande Artisan, mais un script bash).

```bash
./deploy.sh          # Deploiement complet (composer, migrations, cache)
./deploy.sh quick    # Deploiement rapide (pull + cache clear)
```

- Sauvegarde automatique de la BDD avant deploiement
- Mode maintenance pendant le deploiement
- Rollback automatique en cas d'erreur
- Verification HTTP post-deploiement

---

## Planification Cron (production)

Ajouter dans le crontab du serveur :

```cron
* * * * * cd /home2/sc1beal9117/faktur.lu && php artisan schedule:run >> /dev/null 2>&1
```

Ou si les commandes ne sont pas dans le scheduler Laravel, les planifier individuellement :

```cron
# Rappels CRM - toutes les heures
0 * * * * cd /home2/sc1beal9117/faktur.lu && php artisan reminders:send >> /dev/null 2>&1

# Rappels d'essai - tous les jours a 9h
0 9 * * * cd /home2/sc1beal9117/faktur.lu && php artisan trial:send-reminders >> /dev/null 2>&1

# Nettoyage metriques - tous les jours a 3h
0 3 * * * cd /home2/sc1beal9117/faktur.lu && php artisan monitoring:cleanup >> /dev/null 2>&1

# Sauvegarde BDD - tous les jours a 2h
0 2 * * * cd /home2/sc1beal9117/faktur.lu && php artisan backup:run >> /dev/null 2>&1
```
