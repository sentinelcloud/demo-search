<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\AnalyticsSnapshot;
use App\Models\Visitor;
use App\Models\Session;
use App\Models\PageView;
use App\Models\AnalyticsEvent;

class RecordAnalyticsSnapshot implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('reports');
    }

    public function handle(): void
    {
        $now = now();

        $metrics = [
            'total_visitors' => Visitor::count(),
            'total_sessions' => Session::count(),
            'total_page_views' => PageView::count(),
            'total_events' => AnalyticsEvent::count(),
            'active_visitors_1h' => Visitor::where('last_seen_at', '>=', $now->copy()->subHour())->count(),
            'page_views_1h' => PageView::where('created_at', '>=', $now->copy()->subHour())->count(),
            'impressions_1h' => AnalyticsEvent::where('event_name', 'impression')
                ->where('created_at', '>=', $now->copy()->subHour())->count(),
            'clicks_1h' => AnalyticsEvent::where('event_name', 'click')
                ->where('created_at', '>=', $now->copy()->subHour())->count(),
        ];

        $snapshots = [];
        foreach ($metrics as $metric => $value) {
            $snapshots[] = [
                'metric' => $metric,
                'value' => $value,
                'recorded_at' => $now,
            ];
        }

        AnalyticsSnapshot::insert($snapshots);
    }
}
