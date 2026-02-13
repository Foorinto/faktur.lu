<?php

namespace App\Http\Middleware;

use App\Models\RequestMetric;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TrackRequestMetrics
{
    protected float $startTime;

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->shouldTrack($request)) {
            return $next($request);
        }

        $this->startTime = microtime(true);
        DB::enableQueryLog();

        $response = $next($request);

        $this->recordMetrics($request, $response);

        return $response;
    }

    protected function shouldTrack(Request $request): bool
    {
        if (!config('monitoring.enabled', true)) {
            return false;
        }

        // Sampling rate
        $samplingRate = config('monitoring.sampling_rate', 100);
        if ($samplingRate < 100 && rand(1, 100) > $samplingRate) {
            return false;
        }

        // Exclude certain paths
        $excludedPaths = config('monitoring.exclude_paths', []);
        foreach ($excludedPaths as $pattern) {
            if (Str::is($pattern, $request->path())) {
                return false;
            }
        }

        // Only track web requests (not assets)
        $extension = pathinfo($request->path(), PATHINFO_EXTENSION);
        if (in_array($extension, ['js', 'css', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf'])) {
            return false;
        }

        return true;
    }

    protected function recordMetrics(Request $request, Response $response): void
    {
        try {
            $queries = DB::getQueryLog();
            DB::disableQueryLog();

            $queryTime = collect($queries)->sum(fn ($q) => $q['time'] ?? 0);

            RequestMetric::create([
                'url' => Str::limit($request->path(), 497),
                'method' => $request->method(),
                'response_time_ms' => (int) ((microtime(true) - $this->startTime) * 1000),
                'memory_usage_mb' => (int) (memory_get_peak_usage(true) / 1024 / 1024),
                'query_count' => count($queries),
                'query_time_ms' => (int) $queryTime,
                'status_code' => $response->getStatusCode(),
                'user_id' => auth()->id(),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Silently fail - don't break the app if monitoring fails
            report($e);
        }
    }
}
