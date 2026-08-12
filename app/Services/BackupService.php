<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class BackupService
{
    protected string $localPath;

    public function __construct()
    {
        $this->localPath = config('backup.local.path');
    }

    /**
     * Run a full backup: dump, compress, encrypt, upload to cloud, clean old backups.
     */
    public function run(): array
    {
        $startTime = microtime(true);
        $timestamp = now()->format('Y-m-d_His');
        $filename = "backup_{$timestamp}.sql.gz";
        $result = [
            'timestamp' => $timestamp,
            'filename' => $filename,
            'local_path' => null,
            'encrypted' => false,
            'cloud_uploaded' => false,
            'cloud_error' => null,
            'env_backed_up' => false,
            'env_error' => null,
            'local_cleaned' => 0,
            'cloud_cleaned' => 0,
            'duration_seconds' => 0,
            'size_bytes' => 0,
        ];

        $this->ensureDirectory($this->localPath);

        // 1. Dump database
        $dumpPath = "{$this->localPath}/{$filename}";
        $this->dumpDatabase($dumpPath);
        Log::info("[Backup] Database dumped to {$dumpPath}");

        // 2. Encrypt if key is set
        $finalPath = $dumpPath;
        if (config('backup.encryption_key')) {
            $finalPath = $this->encrypt($dumpPath);
            @unlink($dumpPath);
            $result['encrypted'] = true;
            $result['filename'] = basename($finalPath);
            Log::info('[Backup] Backup encrypted');
        }

        $result['local_path'] = $finalPath;
        $result['size_bytes'] = filesize($finalPath);

        // 3. Set restrictive permissions
        chmod($finalPath, 0600);

        // 4. Upload to cloud via rclone
        //
        // L'échec est enregistré dans le résultat, et non seulement journalisé :
        // une sauvegarde qui ne quitte pas le serveur disparaît avec lui. Tant
        // que l'erreur restait confinée au fichier de log, la commande sortait
        // en succès et personne n'apprenait que la copie hors-site manquait —
        // c'est ainsi que des nuits entières ont pu passer sans dépôt distant.
        if (config('backup.cloud.enabled')) {
            try {
                $this->uploadToCloud($finalPath);
                $result['cloud_uploaded'] = true;
                Log::info('[Backup] Uploaded to cloud via rclone');
            } catch (\Throwable $e) {
                $result['cloud_error'] = $e->getMessage();
                Log::error("[Backup] Cloud upload failed: {$e->getMessage()}");
            }
        }

        // 4 bis. Copie du fichier .env
        //
        // Le dump ne contient que la base. Or les champs chiffrés au repos —
        // IBAN, configuration de messagerie — ne se relisent qu'avec APP_KEY,
        // qui vit dans .env et nulle part ailleurs. Restaurer une base sans
        // cette clé rendait donc ces colonnes définitivement illisibles : le
        // seul endroit qui détenait la clé était précisément celui contre la
        // perte duquel on sauvegarde.
        if (config('backup.include_env', true)) {
            try {
                $envPath = $this->backupEnvFile($timestamp);

                if ($envPath) {
                    $result['env_backed_up'] = true;

                    if (config('backup.cloud.enabled')) {
                        $this->uploadToCloud($envPath);
                    }

                    Log::info('[Backup] Fichier .env sauvegardé et chiffré');
                }
            } catch (\Throwable $e) {
                $result['env_error'] = $e->getMessage();
                Log::error("[Backup] Sauvegarde du .env impossible : {$e->getMessage()}");
            }
        }

        // 5. Clean old local backups
        $result['local_cleaned'] = $this->cleanLocal();

        // 6. Clean old cloud backups
        if ($result['cloud_uploaded']) {
            try {
                $result['cloud_cleaned'] = $this->cleanCloud();
            } catch (\Throwable $e) {
                Log::warning("[Backup] Cloud cleanup failed: {$e->getMessage()}");
            }
        }

        $result['duration_seconds'] = round(microtime(true) - $startTime, 2);

        Log::info('[Backup] Completed', $result);

        return $result;
    }

    /**
     * Restore from a backup file.
     */
    public function restore(string $filePath): void
    {
        if (! file_exists($filePath)) {
            throw new RuntimeException("Backup file not found: {$filePath}");
        }

        $workingFile = $filePath;

        // Decrypt if needed
        if (str_ends_with($filePath, '.enc')) {
            if (! config('backup.encryption_key')) {
                throw new RuntimeException('Backup is encrypted but no BACKUP_ENCRYPTION_KEY is set.');
            }
            $workingFile = $this->decrypt($filePath);
        }

        // Decompress and restore
        $this->restoreDatabase($workingFile);

        // Clean up temp decrypted file
        if ($workingFile !== $filePath) {
            @unlink($workingFile);
        }

        Log::info("[Backup] Restored from {$filePath}");
    }

    /**
     * List available local backups.
     */
    public function listLocal(): array
    {
        if (! is_dir($this->localPath)) {
            return [];
        }

        $files = glob("{$this->localPath}/backup_*.{sql.gz,sql.gz.enc}", GLOB_BRACE);

        return collect($files)
            ->map(function ($file) {
                $basename = basename($file);
                preg_match('/backup_(\d{4}-\d{2}-\d{2}_\d{6})/', $basename, $matches);
                $date = isset($matches[1]) ? Carbon::createFromFormat('Y-m-d_His', $matches[1]) : null;

                return [
                    'path' => $file,
                    'filename' => $basename,
                    'size' => filesize($file),
                    'date' => $date?->toDateTimeString(),
                    'encrypted' => str_ends_with($file, '.enc'),
                ];
            })
            ->sortByDesc('date')
            ->values()
            ->all();
    }

    /**
     * Dump the database to a gzipped file.
     */
    protected function dumpDatabase(string $outputPath): void
    {
        $connection = config('backup.database_connection');

        if ($connection === 'sqlite') {
            $this->dumpSqlite($outputPath);
        } else {
            $this->dumpMysql($outputPath);
        }
    }

    protected function dumpMysql(string $outputPath): void
    {
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port', 3306);
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $command = sprintf(
            '%s --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers %s | gzip > %s',
            escapeshellarg((string) config('backup.mysqldump_binary', 'mysqldump')),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($outputPath)
        );

        $process = Process::timeout(300)->run($command);

        if (! $process->successful()) {
            throw new RuntimeException("mysqldump failed: {$process->errorOutput()}");
        }

        if (! file_exists($outputPath) || filesize($outputPath) === 0) {
            throw new RuntimeException('mysqldump produced an empty file.');
        }
    }

    protected function dumpSqlite(string $outputPath): void
    {
        $dbPath = config('database.connections.sqlite.database');

        if (! file_exists($dbPath)) {
            throw new RuntimeException("SQLite database not found: {$dbPath}");
        }

        $tempSql = tempnam(sys_get_temp_dir(), 'backup_');
        $process = Process::timeout(120)->run(
            sprintf('sqlite3 %s .dump > %s', escapeshellarg($dbPath), escapeshellarg($tempSql))
        );

        if (! $process->successful()) {
            @unlink($tempSql);
            throw new RuntimeException("sqlite3 dump failed: {$process->errorOutput()}");
        }

        // Gzip compress
        $process = Process::timeout(120)->run(
            sprintf('gzip -c %s > %s', escapeshellarg($tempSql), escapeshellarg($outputPath))
        );

        @unlink($tempSql);

        if (! $process->successful()) {
            throw new RuntimeException("gzip failed: {$process->errorOutput()}");
        }
    }

    /**
     * Restore a gzipped SQL dump.
     */
    protected function restoreDatabase(string $gzipPath): void
    {
        $connection = config('backup.database_connection');

        if ($connection === 'sqlite') {
            $this->restoreSqlite($gzipPath);
        } else {
            $this->restoreMysql($gzipPath);
        }
    }

    protected function restoreMysql(string $gzipPath): void
    {
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port', 3306);
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $command = sprintf(
            'gunzip -c %s | mysql --host=%s --port=%s --user=%s --password=%s %s',
            escapeshellarg($gzipPath),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database)
        );

        $process = Process::timeout(300)->run($command);

        if (! $process->successful()) {
            throw new RuntimeException("MySQL restore failed: {$process->errorOutput()}");
        }
    }

    protected function restoreSqlite(string $gzipPath): void
    {
        $dbPath = config('database.connections.sqlite.database');

        // Create backup of current database before restore
        if (file_exists($dbPath)) {
            copy($dbPath, "{$dbPath}.pre-restore");
        }

        $tempSql = tempnam(sys_get_temp_dir(), 'restore_');
        $process = Process::timeout(120)->run(
            sprintf('gunzip -c %s > %s', escapeshellarg($gzipPath), escapeshellarg($tempSql))
        );

        if (! $process->successful()) {
            @unlink($tempSql);
            throw new RuntimeException("gunzip failed: {$process->errorOutput()}");
        }

        // Drop existing database and restore
        @unlink($dbPath);
        $process = Process::timeout(120)->run(
            sprintf('sqlite3 %s < %s', escapeshellarg($dbPath), escapeshellarg($tempSql))
        );

        @unlink($tempSql);

        if (! $process->successful()) {
            // Attempt to restore the pre-restore backup
            if (file_exists("{$dbPath}.pre-restore")) {
                copy("{$dbPath}.pre-restore", $dbPath);
            }
            throw new RuntimeException("SQLite restore failed: {$process->errorOutput()}");
        }

        @unlink("{$dbPath}.pre-restore");
    }

    /**
     * Encrypt a file with AES-256-CBC.
     */
    protected function encrypt(string $filePath): string
    {
        $key = config('backup.encryption_key');
        $outputPath = "{$filePath}.enc";

        // La clé passe par l'ENVIRONNEMENT, jamais en argument de commande :
        // sur un hébergement mutualisé, la liste des processus peut être lisible
        // par d'autres comptes du serveur, et `-pass pass:<clé>` l'y exposerait
        // le temps du chiffrement. L'algorithme est inchangé : les archives
        // existantes restent déchiffrables.
        $process = Process::timeout(120)
            ->env(['BACKUP_ENC_PASS' => $key])
            ->run(
                sprintf(
                    'openssl enc -aes-256-cbc -salt -pbkdf2 -in %s -out %s -pass env:BACKUP_ENC_PASS',
                    escapeshellarg($filePath),
                    escapeshellarg($outputPath)
                )
            );

        if (! $process->successful()) {
            throw new RuntimeException("Encryption failed: {$process->errorOutput()}");
        }

        return $outputPath;
    }

    /**
     * Decrypt an encrypted backup file.
     */
    protected function decrypt(string $filePath): string
    {
        $key = config('backup.encryption_key');
        $outputPath = str_replace('.enc', '', $filePath);

        if ($outputPath === $filePath) {
            $outputPath = tempnam(sys_get_temp_dir(), 'decrypt_');
        }

        $process = Process::timeout(120)
            ->env(['BACKUP_ENC_PASS' => $key])
            ->run(
                sprintf(
                    'openssl enc -aes-256-cbc -d -pbkdf2 -in %s -out %s -pass env:BACKUP_ENC_PASS',
                    escapeshellarg($filePath),
                    escapeshellarg($outputPath)
                )
            );

        if (! $process->successful()) {
            throw new RuntimeException("Decryption failed: {$process->errorOutput()}");
        }

        return $outputPath;
    }

    /** Binaire rclone à utiliser (configurable : voir config/backup.php). */
    protected function rcloneBinary(): string
    {
        return (string) config('backup.cloud.binary', 'rclone');
    }

    /**
     * Upload a file to cloud storage via rclone.
     */
    protected function uploadToCloud(string $filePath): void
    {
        $remote = config('backup.cloud.remote');
        $path = config('backup.cloud.path');
        $destination = "{$remote}:{$path}";

        $process = Process::timeout(300)->run(
            sprintf(
                '%s copy %s %s --no-traverse',
                escapeshellarg($this->rcloneBinary()),
                escapeshellarg($filePath),
                escapeshellarg($destination)
            )
        );

        if (! $process->successful()) {
            throw new RuntimeException("rclone upload failed: {$process->errorOutput()}");
        }

        // « Envoyé » doit vouloir dire « présent à destination », pas seulement
        // « rclone est sorti en 0 » : on relit le fichier sur le distant.
        //
        // Mais on le relit PLUSIEURS FOIS. pCloud n'indexe pas instantanément
        // ce qu'il vient de recevoir : interrogé dans la seconde qui suit
        // l'envoi, il répond une liste où le fichier ne figure pas encore. La
        // vérification tombait alors en échec sur des sauvegardes pourtant bien
        // arrivées, et le journal accusait une panne inexistante — ce qui est
        // pire qu'un contrôle absent : une alerte qui se trompe finit par ne
        // plus être lue, et couvre celle qui compte.
        $delais = config('backup.cloud.verify_delays', [2, 5, 10, 20]);

        if ($this->remoteHasFile($destination, basename($filePath), $delais)) {
            return;
        }

        throw new RuntimeException(
            'rclone reported success but the file is not present on the remote: '
            .$destination.'/'.basename($filePath)
        );
    }

    /**
     * Le fichier est-il visible à destination ?
     *
     * Réessaie avec un délai croissant, pour laisser au distant le temps
     * d'indexer. Les délais cumulés restent très en deçà de la fenêtre
     * nocturne : mieux vaut attendre une minute que déclarer une fausse panne.
     *
     * @param  list<int>  $delaisSecondes  Attente avant chacune des relectures suivantes.
     */
    protected function remoteHasFile(string $destination, string $filename, array $delaisSecondes = [2, 5, 10, 20]): bool
    {
        $tentatives = count($delaisSecondes) + 1;

        for ($i = 0; $i < $tentatives; $i++) {
            if ($i > 0) {
                sleep($delaisSecondes[$i - 1]);
            }

            $check = Process::timeout(120)->run(
                sprintf(
                    '%s lsf %s --include %s',
                    escapeshellarg($this->rcloneBinary()),
                    escapeshellarg($destination),
                    escapeshellarg($filename)
                )
            );

            if ($check->successful() && trim($check->output()) !== '') {
                if ($i > 0) {
                    Log::info("[Backup] Fichier visible sur le distant après {$i} relecture(s)");
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Clean old cloud backups based on retention policy via rclone.
     */
    protected function cleanCloud(): int
    {
        $remote = config('backup.cloud.remote');
        $path = config('backup.cloud.path');
        $retentionDays = config('backup.cloud.retention_days', 30);
        $destination = "{$remote}:{$path}";

        // Use rclone delete with --min-age to remove old backup files
        $process = Process::timeout(120)->run(
            sprintf(
                escapeshellarg($this->rcloneBinary()).' delete %s --min-age %dd --include "backup_*" -v 2>&1 | grep -c "Deleted" || echo "0"',
                escapeshellarg($destination),
                $retentionDays
            )
        );

        // Count deletions from rclone output
        $output = trim($process->output());
        $cleaned = is_numeric($output) ? (int) $output : 0;

        if ($cleaned > 0) {
            Log::info("[Backup] Cleaned {$cleaned} old cloud backups");
        }

        return $cleaned;
    }

    /**
     * Clean old local backups based on retention policy.
     */
    protected function cleanLocal(): int
    {
        $retentionDays = config('backup.local.retention_days', 7);
        $cutoff = now()->subDays($retentionDays);
        $cleaned = 0;

        foreach ($this->listLocal() as $backup) {
            if ($backup['date'] && Carbon::parse($backup['date'])->lt($cutoff)) {
                @unlink($backup['path']);
                $cleaned++;
            }
        }

        if ($cleaned > 0) {
            Log::info("[Backup] Cleaned {$cleaned} old local backups");
        }

        return $cleaned;
    }

    /**
     * Copie chiffrée du fichier .env, à côté du dump.
     *
     * Nommée « backup_{horodatage}.env.enc » : le préfixe la fait ramasser par
     * les purges de rétention, locale comme distante, tandis que le suffixe la
     * tient hors de `listLocal()`, qui n'énumère que les dumps restaurables.
     * Elle ne doit jamais apparaître dans la liste des sauvegardes à restaurer.
     *
     * @return string|null Chemin du fichier chiffré, ou null si rien n'a été fait.
     */
    protected function backupEnvFile(string $timestamp): ?string
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            return null;
        }

        // Refus catégorique en l'absence de clé de chiffrement. Le .env porte
        // TOUS les secrets : mot de passe de base, clés Stripe, identifiants de
        // messagerie. Le déposer en clair chez un tiers serait strictement pire
        // que de ne pas le sauvegarder du tout.
        if (! config('backup.encryption_key')) {
            Log::warning(
                '[Backup] .env non sauvegardé : BACKUP_ENCRYPTION_KEY absente. '
                .'Un .env en clair sur un dépôt distant exposerait tous les secrets.'
            );

            return null;
        }

        $copie = "{$this->localPath}/backup_{$timestamp}.env";
        copy($envPath, $copie);
        chmod($copie, 0600);

        $chiffre = $this->encrypt($copie);
        @unlink($copie);
        chmod($chiffre, 0600);

        return $chiffre;
    }

    protected function ensureDirectory(string $path): void
    {
        if (! is_dir($path)) {
            mkdir($path, 0700, true);
        }
    }
}
