<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\PageView;
use App\Models\Session;
use App\Models\VisitorStat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AggregateVisitorStats implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public ?string $specificPeriod = null)
    {
        $this->onQueue('reports');
    }

    public function handle(): void
    {
        $period = $this->specificPeriod
            ? Carbon::parse($this->specificPeriod)->startOfHour()
            : Carbon::now()->subHour()->startOfHour();

        $periodEnd = $period->copy()->endOfHour();

        // Aggregate page views into visitor stats
        $stats = DB::table('page_views')
            ->whereBetween('created_at', [$period, $periodEnd])
            ->selectRaw('COUNT(*) as page_views')
            ->selectRaw('COUNT(DISTINCT visitor_id) as visitors')
            ->first();

        $sessionStats = DB::table('analytics_sessions')
            ->whereBetween('started_at', [$period, $periodEnd])
            ->selectRaw('COUNT(*) as sessions')
            ->selectRaw('SUM(CASE WHEN is_bounce THEN 1 ELSE 0 END) as bounces')
            ->selectRaw('AVG(duration_seconds) as avg_duration')
            ->first();

        $newVisitors = DB::table('visitors')
            ->whereBetween('first_seen_at', [$period, $periodEnd])
            ->count();

        VisitorStat::updateOrCreate(
            ['period' => $period],
            [
                'visitors' => $stats->visitors ?? 0,
                'new_visitors' => $newVisitors,
                'sessions' => $sessionStats->sessions ?? 0,
                'page_views' => $stats->page_views ?? 0,
                'bounces' => $sessionStats->bounces ?? 0,
                'avg_duration_seconds' => round($sessionStats->avg_duration ?? 0, 2),
            ]
        );

        // Chain to other aggregation jobs
        AggregatePageStats::dispatch($period->toIso8601String())->onQueue('reports');
        AggregateReferrerStats::dispatch($period->toIso8601String())->onQueue('reports');
        AggregateDeviceStats::dispatch($period->toIso8601String())->onQueue('reports');
        AggregateEventStats::dispatch($period->toIso8601String())->onQueue('reports');
    }
}
