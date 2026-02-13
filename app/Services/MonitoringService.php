<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\RequestMetric;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MonitoringService
{
    public function getOverview(string $period = '24h'): array
    {
        $since = $this->getPeriodStart($period);

        return [
            'period' => $period,
            'since' => $since->toISOString(),
            'requests' => $this->getRequestStats($since),
            'database' => $this->getDatabaseStats($since),
            'system' => $this->getSystemStats(),
            'application' => $this->getApplicationStats(),
            'alerts' => $this->checkAlerts($since),
        ];
    }

    protected function getPeriodStart(string $period): Carbon
    {
        return match ($period) {
            '1h' => now()->subHour(),
            '24h' => now()->subDay(),
            '7d' => now()->subWeek(),
            '30d' => now()->subMonth(),
            default => now()->subDay(),
        };
    }

    public function getRequestStats(Carbon $since): array
    {
        $metrics = RequestMetric::since($since);
        $totalMinutes = max(1, $since->diffInMinutes(now()));
        $count = $metrics->count();

        $responseTimes = RequestMetric::since($since)
            ->selectRaw('
                AVG(response_time_ms) as avg_time,
                MAX(response_time_ms) as max_time
            ')
            ->first();

        return [
            'total' => $count,
            'avg_response_time' => round($responseTimes->avg_time ?? 0),
            'max_response_time' => $responseTimes->max_time ?? 0,
            'p95_response_time' => $this->getPercentile($since, 'response_time_ms', 95),
            'p99_response_time' => $this->getPercentile($since, 'response_time_ms', 99),
            'requests_per_minute' => round($count / $totalMinutes, 2),
            'error_count' => RequestMetric::since($since)->errors()->count(),
            'error_rate' => $count > 0
                ? round((RequestMetric::since($since)->errors()->count() / $count) * 100, 2)
                : 0,
            'slowest' => $this->getSlowestRequests($since, 10),
        ];
    }

    protected function getPercentile(Carbon $since, string $column, int $percentile): int
    {
        $count = RequestMetric::since($since)->count();
        if ($count === 0) {
            return 0;
        }

        $offset = (int) floor($count * ($percentile / 100));

        $value = RequestMetric::since($since)
            ->orderBy($column)
            ->offset($offset)
            ->limit(1)
            ->value($column);

        return $value ?? 0;
    }

    protected function getSlowestRequests(Carbon $since, int $limit): Collection
    {
        return RequestMetric::since($since)
            ->select(['id', 'url', 'method', 'response_time_ms', 'query_count', 'status_code', 'created_at'])
            ->orderByDesc('response_time_ms')
            ->limit($limit)
            ->get()
            ->map(fn ($metric) => [
                'url' => $metric->url,
                'method' => $metric->method,
                'response_time_ms' => $metric->response_time_ms,
                'query_count' => $metric->query_count,
                'status_code' => $metric->status_code,
                'created_at' => $metric->created_at->toISOString(),
            ]);
    }

    public function getDatabaseStats(Carbon $since): array
    {
        $metrics = RequestMetric::since($since);

        $queryStats = $metrics
            ->selectRaw('
                AVG(query_count) as avg_queries,
                MAX(query_count) as max_queries,
                AVG(query_time_ms) as avg_query_time,
                MAX(query_time_ms) as max_query_time
            ')
            ->first();

        $dbSize = $this->getDatabaseSize();

        return [
            'avg_queries_per_request' => round($queryStats->avg_queries ?? 0, 1),
            'max_queries_per_request' => $queryStats->max_queries ?? 0,
            'avg_query_time_ms' => round($queryStats->avg_query_time ?? 0),
            'max_query_time_ms' => $queryStats->max_query_time ?? 0,
            'database_size_mb' => $dbSize,
            'slow_queries' => $this->getSlowQueryRequests($since, 10),
        ];
    }

    protected function getDatabaseSize(): float
    {
        try {
            $driver = DB::getDriverName();

            if ($driver === 'sqlite') {
                // For SQLite, get the file size
                $dbPath = config('database.connections.sqlite.database');
                if (file_exists($dbPath)) {
                    return round(filesize($dbPath) / 1024 / 1024, 2);
                }
                return 0;
            }

            // For MySQL
            $dbName = config('database.connections.mysql.database');
            $result = DB::select("
                SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables
                WHERE table_schema = ?
            ", [$dbName]);

            return $result[0]->size_mb ?? 0;
        } catch (\Exception) {
            return 0;
        }
    }

    protected function getSlowQueryRequests(Carbon $since, int $limit): Collection
    {
        return RequestMetric::since($since)
            ->select(['url', 'method', 'query_count', 'query_time_ms', 'created_at'])
            ->orderByDesc('query_time_ms')
            ->limit($limit)
            ->get()
            ->map(fn ($metric) => [
                'url' => $metric->url,
                'method' => $metric->method,
                'query_count' => $metric->query_count,
                'query_time_ms' => $metric->query_time_ms,
                'created_at' => $metric->created_at->toISOString(),
            ]);
    }

    public function getSystemStats(): array
    {
        $storagePath = storage_path();

        return [
            'disk_total_gb' => round(disk_total_space('/') / 1024 / 1024 / 1024, 2),
            'disk_free_gb' => round(disk_free_space('/') / 1024 / 1024 / 1024, 2),
            'disk_used_percent' => round((1 - disk_free_space('/') / disk_total_space('/')) * 100, 1),
            'storage_size_mb' => round($this->getDirectorySize($storagePath) / 1024 / 1024, 2),
            'php_version' => PHP_VERSION,
            'php_memory_limit' => ini_get('memory_limit'),
            'laravel_version' => app()->version(),
        ];
    }

    protected function getDirectorySize(string $path): int
    {
        $size = 0;

        try {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)) as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        } catch (\Exception) {
            // Ignore permission errors
        }

        return $size;
    }

    public function getApplicationStats(): array
    {
        $jobsPending = 0;
        $jobsFailed24h = 0;

        try {
            $jobsPending = DB::table('jobs')->count();
            $jobsFailed24h = DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subDay())
                ->count();
        } catch (\Exception) {
            // Tables might not exist
        }

        return [
            'users_total' => User::count(),
            'users_active_24h' => User::where('last_activity_at', '>=', now()->subDay())->count(),
            'users_active_7d' => User::where('last_activity_at', '>=', now()->subWeek())->count(),
            'users_active_30d' => User::where('last_activity_at', '>=', now()->subMonth())->count(),
            'invoices_today' => Invoice::whereDate('created_at', today())->count(),
            'invoices_week' => Invoice::where('created_at', '>=', now()->subWeek())->count(),
            'invoices_month' => Invoice::where('created_at', '>=', now()->subMonth())->count(),
            'jobs_pending' => $jobsPending,
            'jobs_failed_24h' => $jobsFailed24h,
            'metrics_count' => RequestMetric::count(),
            'metrics_oldest' => RequestMetric::min('created_at'),
        ];
    }

    public function checkAlerts(Carbon $since): array
    {
        $thresholds = config('monitoring.thresholds');
        $alerts = [];

        // Response time alert
        $avgResponseTime = RequestMetric::since($since)->avg('response_time_ms') ?? 0;
        $alerts['response_time'] = $this->getAlertLevel($avgResponseTime, $thresholds['response_time']);

        // Query count alert
        $avgQueries = RequestMetric::since($since)->avg('query_count') ?? 0;
        $alerts['query_count'] = $this->getAlertLevel($avgQueries, $thresholds['query_count']);

        // Memory alert
        $avgMemory = RequestMetric::since($since)->avg('memory_usage_mb') ?? 0;
        $alerts['memory'] = $this->getAlertLevel($avgMemory, $thresholds['memory']);

        // Disk usage alert
        $diskUsed = (1 - disk_free_space('/') / disk_total_space('/')) * 100;
        $alerts['disk_usage'] = $this->getAlertLevel($diskUsed, $thresholds['disk_usage']);

        // Failed jobs alert
        try {
            $failedJobs = DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subDay())
                ->count();
            $alerts['failed_jobs'] = $this->getAlertLevel($failedJobs, $thresholds['failed_jobs']);
        } catch (\Exception) {
            $alerts['failed_jobs'] = 'ok';
        }

        // Error rate alert
        $count = RequestMetric::since($since)->count();
        $errorRate = $count > 0
            ? (RequestMetric::since($since)->errors()->count() / $count) * 100
            : 0;
        $alerts['error_rate'] = $this->getAlertLevel($errorRate, $thresholds['error_rate']);

        return $alerts;
    }

    protected function getAlertLevel(float $value, array $threshold): string
    {
        if ($value >= $threshold['critical']) {
            return 'critical';
        }
        if ($value >= $threshold['warning']) {
            return 'warning';
        }
        return 'ok';
    }

    public function getTimeSeriesData(string $period = '24h', string $interval = 'hour'): array
    {
        $since = $this->getPeriodStart($period);
        $driver = DB::getDriverName();

        // SQLite uses strftime, MySQL uses DATE_FORMAT
        if ($driver === 'sqlite') {
            $format = match ($interval) {
                'minute' => '%Y-%m-%d %H:%M',
                'hour' => '%Y-%m-%d %H:00',
                'day' => '%Y-%m-%d',
                default => '%Y-%m-%d %H:00',
            };
            $dateExpr = "strftime('{$format}', created_at)";
        } else {
            $format = match ($interval) {
                'minute' => '%Y-%m-%d %H:%i',
                'hour' => '%Y-%m-%d %H:00',
                'day' => '%Y-%m-%d',
                default => '%Y-%m-%d %H:00',
            };
            $dateExpr = "DATE_FORMAT(created_at, '{$format}')";
        }

        $data = RequestMetric::since($since)
            ->selectRaw("
                {$dateExpr} as time_bucket,
                COUNT(*) as request_count,
                AVG(response_time_ms) as avg_response_time,
                AVG(query_count) as avg_queries,
                SUM(CASE WHEN status_code >= 500 THEN 1 ELSE 0 END) as error_count
            ")
            ->groupBy('time_bucket')
            ->orderBy('time_bucket')
            ->get();

        return $data->map(fn ($row) => [
            'time' => $row->time_bucket,
            'requests' => $row->request_count,
            'avg_response_time' => round($row->avg_response_time ?? 0),
            'avg_queries' => round($row->avg_queries ?? 0, 1),
            'errors' => $row->error_count,
        ])->toArray();
    }

    public function cleanupOldMetrics(): int
    {
        $days = config('monitoring.retention_days', 30);

        return RequestMetric::where('created_at', '<', now()->subDays($days))->delete();
    }
}
