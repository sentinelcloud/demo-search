<?php

namespace App\Jobs;

use App\Events\AnalyticsEventProcessed;
use App\Models\AnalyticsEvent;
use App\Models\Product;
use App\Services\DeviceDetectorService;
use App\Services\FingerprintService;
use App\Services\SessionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAnalyticsEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $eventName,
        public ?int $productId,
        public ?string $path,
        public ?array $metadata,
        public string $ip,
        public string $userAgent,
        public ?int $screenWidth = null,
        public ?int $screenHeight = null,
        public ?string $language = null,
        public array $utmParams = [],
        public string $timestamp = '',
    ) {
        $this->timestamp = $this->timestamp ?: now()->toISOString();
        $this->onQueue('analytics');
    }

    public function handle(
        FingerprintService $fingerprint,
        SessionService $sessionService,
        DeviceDetectorService $deviceDetector,
    ): void {
        // Resolve or create visitor
        $visitor = $fingerprint->resolveVisitor($this->ip, $this->userAgent);

        // Parse device info
        $deviceInfo = $deviceDetector->parse($this->userAgent);

        // Resolve or create session
        $session = $sessionService->resolveSession(
            visitor: $visitor,
            path: $this->path ?? '/',
            referrer: null,
            deviceInfo: $deviceInfo,
            screenWidth: $this->screenWidth,
            screenHeight: $this->screenHeight,
            language: $this->language,
            utmParams: $this->utmParams,
            ipHash: hash('sha256', $this->ip),
        );

        // Validate product exists to avoid FK violations (e.g. stale Meilisearch index)
        $productId = $this->productId && Product::where('id', $this->productId)->exists()
            ? $this->productId
            : null;

        // Create analytics event
        $analyticsEvent = AnalyticsEvent::create([
            'visitor_id' => $visitor->id,
            'session_id' => $session->id,
            'event_name' => $this->eventName,
            'product_id' => $productId,
            'path' => $this->path,
            'metadata' => $this->metadata,
            'created_at' => $this->timestamp,
        ]);

        // Broadcast the event via Reverb for the live event feed
        try {
            broadcast(new AnalyticsEventProcessed([
                'id' => $analyticsEvent->id,
                'event_name' => $analyticsEvent->event_name,
                'product_id' => $analyticsEvent->product_id,
                'path' => $analyticsEvent->path,
                'created_at' => $analyticsEvent->created_at->toIso8601String(),
                'product' => $analyticsEvent->product ? [
                    'id' => $analyticsEvent->product->id,
                    'title' => $analyticsEvent->product->title,
                ] : null,
            ]));
        } catch (\Throwable) {
            // Don't fail the job if broadcasting fails
        }

        // Update visitor last seen
        $visitor->update(['last_seen_at' => now()]);

        // Chain to real-time counter update
        UpdateRealtimeCounters::dispatch(
            $visitor->fingerprint,
            $this->eventName,
            $this->productId,
        )->onQueue('analytics');
    }
}
