<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->check(fn () => DB::select('select 1')),
            'redis' => $this->check(fn () => Redis::ping()),
            // Confirms the queue connection is reachable, not that a worker
            // is actively consuming it — size() round-trips to Redis without
            // enqueuing anything a worker would need to process.
            'queue' => $this->check(fn () => Queue::size()),
            // S3-compatible object storage (MinIO in dev) — catalog/review
            // images live here; a broken connection here doesn't fail
            // database/redis checks but does break image uploads.
            'storage' => $this->check(fn () => Storage::disk('s3')->exists('__health_check__')),
        ];

        $allOk = ! in_array(false, array_column($checks, 'ok'), true);

        return ApiResponse::success([
            'status' => $allOk ? 'ok' : 'degraded',
            'checks' => $checks,
        ], $allOk ? 200 : 503);
    }

    private function check(callable $probe): array
    {
        try {
            $probe();

            return ['ok' => true];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }
}
