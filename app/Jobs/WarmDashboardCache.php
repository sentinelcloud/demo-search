<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\AnalyticsReport;
use Illuminate\Support\Facades\Cache;

class WarmDashboardCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('reports');
    }

    public function handle(): void
    {
        // Get the latest dashboard report
        $report = AnalyticsReport::where('report_type', 'dashboard_summary')
            ->latest('generated_at')
            ->first();

        if (!$report) {
            return;
        }

        // Get time series data for charts
        $timeSeries = \App\Models\VisitorStat::where('period', '>=', now()->subDay())
            ->orderBy('period')
            ->get(['period', 'visitors', 'page_views', 'sessions', 'bounces'])
            ->toArray();

        // Get live Redis counters
        $liveCounters = $this->getLiveCounters();

        $dashboardData = [
            'report' => $report->data,
            'report_meta' => [
                'generated_at' => $report->generated_at->toIso8601String(),
                'generation_time_ms' => $report->generation_time_ms,
                'job_batch_id' => $report->job_batch_id,
            ],
            'time_series' => $timeSeries,
            'live' => $liveCounters,
        ];

        Cache::tags(['analytics', 'dashboard'])->put('dashboard_data', $dashboardData, 600);
    }

    private function getLiveCounters(): array
    {
        $redis = app('redis');

        return [
            'page_views_today' => (int) ($redis->get('analytics:live:page_views') ?? 0),
            'events_today' => (int) ($redis->get('analytics:live:events:impression') ?? 0) +
                              (int) ($redis->get('analytics:live:events:click') ?? 0),
            'last_event_at' => $redis->get('analytics:live:last_event_at'),
        ];
    }
}
