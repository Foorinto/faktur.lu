<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class BackupRunCommand extends Command
{
    protected $signature = 'backup:run
                            {--no-cloud : Skip cloud upload (rclone)}
                            {--no-cleanup : Skip old backup cleanup}';

    protected $description = 'Run a database backup (dump, compress, encrypt, upload to cloud via rclone)';

    public function handle(BackupService $service): int
    {
        if (! config('backup.enabled')) {
            $this->warn('Backup is disabled. Set BACKUP_ENABLED=true to enable.');

            return self::SUCCESS;
        }

        $this->info('Starting backup...');

        // Temporarily override cloud upload if --no-cloud
        if ($this->option('no-cloud')) {
            config(['backup.cloud.enabled' => false]);
        }

        try {
            $result = $service->run();

            $this->info("Backup completed in {$result['duration_seconds']}s");
            $this->table(
                ['Property', 'Value'],
                [
                    ['Filename', $result['filename']],
                    ['Size', $this->formatSize($result['size_bytes'])],
                    ['Encrypted', $result['encrypted'] ? 'Yes' : 'No'],
                    ['Cloud', $result['cloud_uploaded'] ? 'Uploaded' : 'Skipped'],
                    ['Old local cleaned', $result['local_cleaned']],
                    ['Old cloud cleaned', $result['cloud_cleaned']],
                    ['Duration', "{$result['duration_seconds']}s"],
                ]
            );

            if (config('backup.notify_on_success') && config('backup.notification_email')) {
                $this->sendNotification('success', $result);
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Backup failed: {$e->getMessage()}");

            if (config('backup.notify_on_failure') && config('backup.notification_email')) {
                $this->sendNotification('failure', ['error' => $e->getMessage()]);
            }

            return self::FAILURE;
        }
    }

    protected function sendNotification(string $type, array $data): void
    {
        $email = config('backup.notification_email');
        $appName = config('app.name', 'faktur.lu');

        if ($type === 'success') {
            Mail::raw(
                "Backup completed successfully.\n\n"
                . "File: {$data['filename']}\n"
                . "Size: {$this->formatSize($data['size_bytes'])}\n"
                . "Cloud: " . ($data['cloud_uploaded'] ? 'Uploaded' : 'Skipped') . "\n"
                . "Duration: {$data['duration_seconds']}s\n",
                function ($message) use ($email, $appName) {
                    $message->to($email)
                        ->subject("[{$appName}] Backup completed successfully");
                }
            );
        } else {
            Mail::raw(
                "Backup FAILED!\n\n"
                . "Error: {$data['error']}\n\n"
                . "Please check the server logs for details.",
                function ($message) use ($email, $appName) {
                    $message->to($email)
                        ->subject("[{$appName}] BACKUP FAILED - Action required");
                }
            );
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
