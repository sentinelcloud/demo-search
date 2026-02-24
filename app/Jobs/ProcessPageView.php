<?php

namespace App\Jobs;

use App\Events\AnalyticsEventProcessed;
use App\Models\PageView;
use App\Services\DeviceDetectorService;
use App\Services\FingerprintService;
use App\Services\SessionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPageView implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $path,
        public ?string $title,
        public ?string $referrer,
        public string $ip,
        public string $userAgent,
        public ?int $screenWidth,
        public ?int $screenHeight,
        public ?string $language,
        public array $utmParams,
        public string $timestamp,
    ) {
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
            path: $this->path,
            referrer: $this->referrer,
            deviceInfo: $deviceInfo,
            screenWidth: $this->screenWidth,
            screenHeight: $this->screenHeight,
            language: $this->language,
            utmParams: $this->utmParams,
            ipHash: hash('sha256', $this->ip),
        );

        // Create page view
        $pageView = PageView::create([
            'visitor_id' => $visitor->id,
            'session_id' => $session->id,
            'path' => $this->path,
            'title' => $this->title,
            'referrer' => $this->referrer,
            'created_at' => $this->timestamp,
        ]);

        // Broadcast the pageview event via Reverb for the live event feed
        try {
            broadcast(new AnalyticsEventProcessed([
                'id' => $pageView->id,
                'event_name' => 'pageview',
                'product_id' => null,
                'path' => $this->path,
                'created_at' => $pageView->created_at->toIso8601String(),
                'product' => null,
            ]));
        } catch (\Throwable) {
            // Don't fail the job if broadcasting fails
        }

        // Update visitor stats
        $visitor->increment('total_page_views');
        $visitor->update(['last_seen_at' => now()]);

        // Chain to real-time counter update
        UpdateRealtimeCounters::dispatch(
            $visitor->fingerprint,
            'pageview',
            null,
        )->onQueue('analytics');
    }
}
