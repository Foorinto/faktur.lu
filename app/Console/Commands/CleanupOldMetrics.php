<?php

namespace App\Console\Commands;

use App\Models\AbuseEvent;
use App\Services\MonitoringService;
use Illuminate\Console\Command;

class CleanupOldMetrics extends Command
{
    protected $signature = 'monitoring:cleanup';

    protected $description = 'Delete old request metrics and abuse events beyond their retention periods';

    public function handle(MonitoringService $monitoringService): int
    {
        $deleted = $monitoringService->cleanupOldMetrics();

        $this->info("Deleted {$deleted} old metrics.");

        // Journal anti-abus (FEAT-138) : 90 jours, voir config/abuse.php.
        $evenements = (new AbuseEvent)->pruneAll();

        $this->info("Deleted {$evenements} old abuse events.");

        return self::SUCCESS;
    }
}
