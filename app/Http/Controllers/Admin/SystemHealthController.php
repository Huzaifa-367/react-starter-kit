<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;
use Throwable;

class SystemHealthController extends Controller
{
    /**
     * Check connection/health status of critical components.
     */
    public function status(): Response|JsonResponse
    {
        // 1. Database Check
        $dbStatus = 'ok';
        $dbError = null;
        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            $dbStatus = 'failed';
            $dbError = $e->getMessage();
        }

        // 2. Redis Check
        $redisStatus = 'ok';
        $redisError = null;
        $redisClient = (string) config('database.redis.client', 'phpredis');
        if ($redisClient === 'phpredis' && !extension_loaded('redis')) {
            $redisStatus = 'warning';
            $redisError = 'PHP Redis extension is not installed. Set REDIS_CLIENT=predis or install ext-redis.';
        } else {
            try {
                Redis::connection()->ping();
            } catch (Throwable $e) {
                $redisStatus = 'failed';
                $redisError = $e->getMessage();
            }
        }

        // 3. Cache Check
        $cacheStatus = 'ok';
        $cacheError = null;
        try {
            Cache::put('health_check_ping', 'pong', 10);
            if (Cache::get('health_check_ping') !== 'pong') {
                throw new \Exception("Cache read/write mismatch.");
            }
        } catch (Throwable $e) {
            $cacheStatus = 'failed';
            $cacheError = $e->getMessage();
        }

        // 4. Queue Check
        $queueStatus = 'ok';
        $queueSize = 0;
        $queueError = null;
        $queueConnection = (string) config('queue.default', 'sync');
        if (
            $queueConnection === 'redis' &&
            $redisClient === 'phpredis' &&
            !extension_loaded('redis')
        ) {
            $queueStatus = 'warning';
            $queueError = 'Redis queue driver requires ext-redis when REDIS_CLIENT=phpredis. Set REDIS_CLIENT=predis or install ext-redis.';
        } else {
            try {
                $queueSize = Queue::size();
            } catch (Throwable $e) {
                $queueStatus = 'failed';
                $queueError = $e->getMessage();
            }
        }

        // 5. Disk Check
        $diskStatus = 'ok';
        $diskFree = 0;
        $diskTotal = 0;
        $diskUsage = 0;
        try {
            $diskFree = disk_free_space(base_path());
            $diskTotal = disk_total_space(base_path());
            $diskUsage = $diskTotal > 0 ? round((($diskTotal - $diskFree) / $diskTotal) * 100, 2) : 0;
            if ($diskUsage > 90) {
                $diskStatus = 'warning';
            }
        } catch (Throwable $e) {
            $diskStatus = 'failed';
        }

        $healthData = [
            'database' => [
                'status' => $dbStatus,
                'error' => $dbError,
            ],
            'redis' => [
                'status' => $redisStatus,
                'error' => $redisError,
            ],
            'cache' => [
                'status' => $cacheStatus,
                'error' => $cacheError,
            ],
            'queue' => [
                'status' => $queueStatus,
                'size' => $queueSize,
                'error' => $queueError,
            ],
            'disk' => [
                'status' => $diskStatus,
                'free_space' => $this->formatBytes($diskFree),
                'total_space' => $this->formatBytes($diskTotal),
                'usage_percent' => $diskUsage,
            ],
            'system' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'server_time' => now()->toIso8601String(),
            ]
        ];

        if (request()->wantsJson()) {
            return response()->json($healthData);
        }

        return Inertia::render('admin/system-health/status', [
            'health' => $healthData,
        ]);
    }

    /**
     * Helper to format bytes to human readable form.
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
