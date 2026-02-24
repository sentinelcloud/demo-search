<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;

class UpdateRealtimeCounters implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $visitorFingerprint,
        public string $eventType,
        public ?int $productId,
    ) {
        $this->onQueue('analytics');
    }

    public function handle(): void
    {
        $redis = Redis::connection();
        $today = now()->format('Y-m-d');
        $hour = now()->format('Y-m-d-H');
        $ttl = 86400 * 2; // 2 days

        // Use Redis pipeline for efficiency (demo: shows off Redis pipelining)
        $redis->pipeline(function ($pipe) use ($today, $hour, $ttl) {
            // Global live counters
            $pipe->incr("analytics:live:page_views");
            $pipe->expire("analytics:live:page_views", $ttl);

            // Unique visitors via HyperLogLog (demo: shows off HLL)
            $pipe->pfadd("analytics:live:visitors:{$today}", [$this->visitorFingerprint]);
            $pipe->expire("analytics:live:visitors:{$today}", $ttl);

            // Hourly counters
            $pipe->incr("analytics:hourly:{$hour}:page_views");
            $pipe->expire("analytics:hourly:{$hour}:page_views", $ttl);

            $pipe->pfadd("analytics:hourly:{$hour}:visitors", [$this->visitorFingerprint]);
            $pipe->expire("analytics:hourly:{$hour}:visitors", $ttl);

            // Event-specific counters
            $pipe->incr("analytics:live:events:{$this->eventType}");
            $pipe->expire("analytics:live:events:{$this->eventType}", $ttl);

            // Product-specific counters
            if ($this->productId) {
                $pipe->incr("analytics:live:product:{$this->productId}:{$this->eventType}");
                $pipe->expire("analytics:live:product:{$this->productId}:{$this->eventType}", $ttl);
            }

            // Timestamp of last event
            $pipe->set("analytics:live:last_event_at", now()->toIso8601String());
            $pipe->expire("analytics:live:last_event_at", $ttl);
        });
    }
}
