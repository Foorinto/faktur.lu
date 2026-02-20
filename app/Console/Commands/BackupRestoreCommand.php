<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupRestoreCommand extends Command
{
    protected $signature = 'backup:restore
                            {file? : Path to the backup file (or choose from list)}';

    protected $description = 'Restore the database from a backup file';

    public function handle(BackupService $service): int
    {
        $filePath = $this->argument('file');

        // If no file specified, show available backups
        if (! $filePath) {
            $backups = $service->listLocal();

            if (empty($backups)) {
                $this->warn('No local backups found.');

                return self::FAILURE;
            }

            $this->info('Available backups:');

            $rows = collect($backups)->map(function ($b, $i) {
                return [
                    $i + 1,
                    $b['filename'],
                    $this->formatSize($b['size']),
                    $b['date'] ?? 'Unknown',
                    $b['encrypted'] ? 'Yes' : 'No',
                ];
            })->all();

            $this->table(['#', 'Filename', 'Size', 'Date', 'Encrypted'], $rows);

            $choice = $this->ask('Enter the number of the backup to restore (or "cancel")');

            if ($choice === 'cancel' || ! is_numeric($choice)) {
                $this->info('Restore cancelled.');

                return self::SUCCESS;
            }

            $index = (int) $choice - 1;
            if (! isset($backups[$index])) {
                $this->error('Invalid selection.');

                return self::FAILURE;
            }

            $filePath = $backups[$index]['path'];
        }

        // Confirm
        $this->warn('WARNING: This will OVERWRITE the current database!');
        $this->line("File: {$filePath}");

        if (! $this->confirm('Are you sure you want to continue?')) {
            $this->info('Restore cancelled.');

            return self::SUCCESS;
        }

        try {
            $service->restore($filePath);
            $this->info('Database restored successfully.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Restore failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    protected function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $size = (float) $bytes;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2) . ' ' . $units[$i];
    }
}
