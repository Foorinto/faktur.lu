# Commandes Backup

## Lancer un backup

```bash
# Backup complet (local + cloud si activé)
php artisan backup:run

# Backup local uniquement (sans upload cloud)
php artisan backup:run --no-cloud

# Backup sans nettoyage des anciens fichiers
php artisan backup:run --no-cleanup
```

## Lister les backups

```bash
php artisan backup:list
```

## Restaurer un backup

```bash
# Choix interactif parmi les backups disponibles
php artisan backup:restore

# Restaurer un fichier spécifique
php artisan backup:restore storage/app/backups/backup_2026-02-19_163834.sql.gz
```

## Configuration rclone (cloud)

```bash
# Installer rclone
brew install rclone

# Configurer un remote (pCloud, S3, Google Drive, etc.)
rclone config

# Vérifier les remotes configurés
rclone listremotes

# Tester l'accès au remote
rclone lsd pcloud:/

# Lister les backups sur le cloud
rclone ls pcloud:/Backups/Facturation
```

## Variables .env

```env
# Backup local
BACKUP_ENABLED=true
BACKUP_SCHEDULE_TIME=03:00
BACKUP_RETENTION_LOCAL=7
BACKUP_LOCAL_PATH=                    # défaut: storage/app/backups

# Chiffrement (optionnel)
BACKUP_ENCRYPTION_KEY=                # laisser vide pour désactiver

# Notifications (optionnel)
BACKUP_NOTIFICATION_EMAIL=

# Cloud via rclone (optionnel)
BACKUP_CLOUD_ENABLED=false
BACKUP_CLOUD_REMOTE=pcloud           # nom du remote rclone
BACKUP_CLOUD_PATH=/Backups/Facturation
BACKUP_CLOUD_RETENTION_DAYS=30
```

## Planification automatique

Le backup tourne automatiquement tous les jours à l'heure configurée (`BACKUP_SCHEDULE_TIME`, défaut 03:00).

Vérifier que le scheduler Laravel est actif :

```bash
# Voir les tâches planifiées
php artisan schedule:list

# Lancer le scheduler manuellement (pour tester)
php artisan schedule:run
```

En production, ajouter ce cron :

```cron
* * * * * cd /chemin/vers/projet && php artisan schedule:run >> /dev/null 2>&1
```
