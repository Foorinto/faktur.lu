<?php

namespace App\Console\Commands;

use App\Services\MonitoringService;
use Illuminate\Console\Command;

class CleanupOldMetrics extends Command
{
    protected $signature = 'monitoring:cleanup';

    protected $description = 'Delete old request metrics beyond retention period';

    public function handle(MonitoringService $monitoringService): int
    {
        $deleted = $monitoringService->cleanupOldMetrics();

        $this->info("Deleted {$deleted} old metrics.");

        return self::SUCCESS;
    }
}
