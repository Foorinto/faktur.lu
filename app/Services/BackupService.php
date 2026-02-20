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
        if (config('backup.cloud.enabled')) {
            try {
                $this->uploadToCloud($finalPath);
                $result['cloud_uploaded'] = true;
                Log::info('[Backup] Uploaded to cloud via rclone');
            } catch (\Throwable $e) {
                Log::error("[Backup] Cloud upload failed: {$e->getMessage()}");
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
            'mysqldump --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers %s | gzip > %s',
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

        $process = Process::timeout(120)->run(
            sprintf(
                'openssl enc -aes-256-cbc -salt -pbkdf2 -in %s -out %s -pass pass:%s',
                escapeshellarg($filePath),
                escapeshellarg($outputPath),
                escapeshellarg($key)
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

        $process = Process::timeout(120)->run(
            sprintf(
                'openssl enc -aes-256-cbc -d -pbkdf2 -in %s -out %s -pass pass:%s',
                escapeshellarg($filePath),
                escapeshellarg($outputPath),
                escapeshellarg($key)
            )
        );

        if (! $process->successful()) {
            throw new RuntimeException("Decryption failed: {$process->errorOutput()}");
        }

        return $outputPath;
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
            sprintf('rclone copy %s %s --no-traverse', escapeshellarg($filePath), escapeshellarg($destination))
        );

        if (! $process->successful()) {
            throw new RuntimeException("rclone upload failed: {$process->errorOutput()}");
        }
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
                'rclone delete %s --min-age %dd --include "backup_*" -v 2>&1 | grep -c "Deleted" || echo "0"',
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

    protected function ensureDirectory(string $path): void
    {
        if (! is_dir($path)) {
            mkdir($path, 0700, true);
        }
    }
}
