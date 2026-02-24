<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsEvent;
use App\Models\AnalyticsReport;
use App\Models\DeviceStat;
use App\Models\PageStat;
use App\Models\ReferrerStat;
use App\Models\VisitorStat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsReportController extends Controller
{
    public function index(Request $request): Response
    {
        $period = $request->get('period', '24h');
        $now = now();

        $periodStart = match ($period) {
            '1h' => $now->copy()->subHour(),
            '24h' => $now->copy()->subDay(),
            '7d' => $now->copy()->subWeek(),
            '30d' => $now->copy()->subMonth(),
            default => $now->copy()->subDay(),
        };

        // Try to use cached dashboard report first
        $cachedDashboard = Cache::tags(['analytics', 'dashboard'])->get('dashboard:warm');

        // Overview stats
        $overview = $cachedDashboard['overview'] ?? $this->buildOverviewStats($periodStart);

        // Time series for chart
        $timeSeries = $cachedDashboard['time_series'] ?? $this->buildTimeSeries($periodStart, $period);

        // Top pages
        $topPages = PageStat::where('period', '>=', $periodStart)
            ->selectRaw('path, SUM(page_views) as total_views, SUM(visitors) as total_visitors')
            ->groupBy('path')
            ->orderByDesc('total_views')
            ->limit(20)
            ->get();

        // Top referrers
        $topReferrers = ReferrerStat::where('period', '>=', $periodStart)
            ->selectRaw('referrer_name, SUM(visitors) as total_visitors, SUM(sessions) as total_sessions')
            ->groupBy('referrer_name')
            ->orderByDesc('total_visitors')
            ->limit(20)
            ->get();

        // Device breakdown
        $browsers = DeviceStat::where('period', '>=', $periodStart)
            ->selectRaw('browser, SUM(visitors) as total_visitors')
            ->groupBy('browser')
            ->orderByDesc('total_visitors')
            ->limit(10)
            ->get();

        $operatingSystems = DeviceStat::where('period', '>=', $periodStart)
            ->selectRaw('os, SUM(visitors) as total_visitors')
            ->groupBy('os')
            ->orderByDesc('total_visitors')
            ->limit(10)
            ->get();

        $deviceTypes = DeviceStat::where('period', '>=', $periodStart)
            ->selectRaw('device_type, SUM(visitors) as total_visitors')
            ->groupBy('device_type')
            ->orderByDesc('total_visitors')
            ->get();

        // Top products by impressions
        $topProducts = AnalyticsEvent::where('created_at', '>=', $periodStart)
            ->where('event_name', 'product_impression')
            ->whereNotNull('product_id')
            ->selectRaw('product_id, COUNT(*) as impressions')
            ->groupBy('product_id')
            ->orderByDesc('impressions')
            ->limit(10)
            ->with('product:id,title,brand,price')
            ->get()
            ->map(function ($item) use ($periodStart) {
                $clicks = AnalyticsEvent::where('created_at', '>=', $periodStart)
                    ->where('event_name', 'product_click')
                    ->where('product_id', $item->product_id)
                    ->count();

                return [
                    'product' => $item->product,
                    'impressions' => $item->impressions,
                    'clicks' => $clicks,
                    'ctr' => $item->impressions > 0 ? round(($clicks / $item->impressions) * 100, 2) : 0,
                ];
            });

        // Recent generated reports (job batches)
        $recentReports = AnalyticsReport::latest('generated_at')
            ->limit(10)
            ->get(['id', 'report_type', 'period_start', 'period_end', 'generated_at', 'generation_time_ms', 'job_batch_id']);

        return Inertia::render('Admin/Reports', [
            'period' => $period,
            'overview' => $overview,
            'timeSeries' => $timeSeries,
            'topPages' => $topPages,
            'topReferrers' => $topReferrers,
            'browsers' => $browsers,
            'operatingSystems' => $operatingSystems,
            'deviceTypes' => $deviceTypes,
            'topProducts' => $topProducts,
            'recentReports' => $recentReports,
        ]);
    }

    private function buildOverviewStats($periodStart): array
    {
        $stats = VisitorStat::where('period', '>=', $periodStart)->get();

        return [
            'total_visitors' => $stats->sum('visitors'),
            'new_visitors' => $stats->sum('new_visitors'),
            'total_sessions' => $stats->sum('sessions'),
            'total_page_views' => $stats->sum('page_views'),
            'total_bounces' => $stats->sum('bounces'),
            'bounce_rate' => $stats->sum('sessions') > 0
                ? round(($stats->sum('bounces') / $stats->sum('sessions')) * 100, 1)
                : 0,
            'avg_duration' => $stats->avg('avg_duration_seconds') ? round($stats->avg('avg_duration_seconds')) : 0,
        ];
    }

    private function buildTimeSeries($periodStart, string $period): array
    {
        $groupFormat = match ($period) {
            '1h' => 'Y-m-d H:i',
            '24h' => 'Y-m-d H:00',
            '7d', '30d' => 'Y-m-d',
            default => 'Y-m-d H:00',
        };

        return VisitorStat::where('period', '>=', $periodStart)
            ->orderBy('period')
            ->get()
            ->groupBy(fn ($stat) => $stat->period->format($groupFormat))
            ->map(fn ($group, $key) => [
                'date' => $key,
                'visitors' => $group->sum('visitors'),
                'page_views' => $group->sum('page_views'),
                'sessions' => $group->sum('sessions'),
            ])
            ->values()
            ->toArray();
    }
}
