<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\PageView;
use App\Models\AnalyticsEvent;
use App\Models\Session;
use App\Models\AnalyticsSnapshot;
use Carbon\Carbon;

class PruneRawAnalytics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    public function __construct(public int $eventDays = 30, public int $sessionDays = 90)
    {
        $this->onQueue('reports');
    }

    public function handle(): void
    {
        $eventCutoff = Carbon::now()->subDays($this->eventDays);
        $sessionCutoff = Carbon::now()->subDays($this->sessionDays);
        $snapshotCutoff = Carbon::now()->subDays(30);

        // Delete old page views in chunks
        $deletedPv = 0;
        do {
            $count = PageView::where('created_at', '<', $eventCutoff)
                ->limit(1000)
                ->delete();
            $deletedPv += $count;
        } while ($count > 0);

        // Delete old analytics events in chunks
        $deletedEvents = 0;
        do {
            $count = AnalyticsEvent::where('created_at', '<', $eventCutoff)
                ->limit(1000)
                ->delete();
            $deletedEvents += $count;
        } while ($count > 0);

        // Delete old sessions
        $deletedSessions = 0;
        do {
            $count = Session::where('started_at', '<', $sessionCutoff)
                ->limit(1000)
                ->delete();
            $deletedSessions += $count;
        } while ($count > 0);

        // Delete old snapshots
        AnalyticsSnapshot::where('recorded_at', '<', $snapshotCutoff)->delete();

        \Illuminate\Support\Facades\Log::info('Analytics pruning completed', [
            'page_views_deleted' => $deletedPv,
            'events_deleted' => $deletedEvents,
            'sessions_deleted' => $deletedSessions,
        ]);
    }
}
