<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupListCommand extends Command
{
    protected $signature = 'backup:list';

    protected $description = 'List all available local backups';

    public function handle(BackupService $service): int
    {
        $backups = $service->listLocal();

        if (empty($backups)) {
            $this->info('No local backups found.');

            return self::SUCCESS;
        }

        $rows = collect($backups)->map(function ($b) {
            return [
                $b['filename'],
                $this->formatSize($b['size']),
                $b['date'] ?? 'Unknown',
                $b['encrypted'] ? 'Yes' : 'No',
            ];
        })->all();

        $this->table(['Filename', 'Size', 'Date', 'Encrypted'], $rows);
        $this->info(count($backups) . ' backup(s) found in ' . config('backup.local.path'));

        return self::SUCCESS;
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
