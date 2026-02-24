<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsEvent;
use App\Models\PageView;
use App\Models\Visitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsDashboardController extends Controller
{
    /**
     * Realtime analytics dashboard — all data comes from Redis + recent DB rows.
     */
    public function index(): Response
    {
        $horizonStats = $this->getHorizonStats();

        // Recent page views (last 50, straight from DB — no aggregation)
        $recentPageViews = PageView::with('visitor:id,fingerprint')
            ->latest('created_at')
            ->limit(50)
            ->get(['id', 'visitor_id', 'session_id', 'path', 'title', 'referrer', 'created_at']);

        // Recent events
        $recentEvents = AnalyticsEvent::with('product:id,title')
            ->latest('created_at')
            ->limit(50)
            ->get(['id', 'event_name', 'product_id', 'path', 'metadata', 'created_at']);

        // Quick DB counts for the last hour (lightweight queries)
        $oneHourAgo = now()->subHour();
        $pageViewsLastHour = PageView::where('created_at', '>=', $oneHourAgo)->count();
        $eventsLastHour = AnalyticsEvent::where('created_at', '>=', $oneHourAgo)->count();
        $visitorsLastHour = Visitor::where('last_seen_at', '>=', $oneHourAgo)->count();

        return Inertia::render('Admin/Analytics', [
            'recentPageViews' => $recentPageViews,
            'recentEvents' => $recentEvents,
            'lastHour' => [
                'page_views' => $pageViewsLastHour,
                'events' => $eventsLastHour,
                'visitors' => $visitorsLastHour,
            ],
            'horizonStats' => $horizonStats,
            'viewer' => [
                'id' => session('admin_viewer_id', 'unknown'),
                'name' => session('admin_viewer_name', 'Anonymous'),
                'color' => session('admin_viewer_color', '#3b82f6'),
            ],
        ]);
    }

    /**
     * Polled by the frontend every few seconds for live Redis counters.
     */
    public function live(): JsonResponse
    {
        try {
            $today = now()->format('Y-m-d');
            $hour = now()->format('Y-m-d-H');

            // Read counters written by UpdateRealtimeCounters job
            $pageViewsTotal = (int) Redis::get('analytics:live:page_views') ?: 0;
            $pageViewsThisHour = (int) Redis::get("analytics:hourly:{$hour}:page_views") ?: 0;
            $lastEventAt = Redis::get('analytics:live:last_event_at');

            // Event-type counters
            $pageviews = (int) Redis::get('analytics:live:events:pageview') ?: 0;
            $impressions = (int) Redis::get('analytics:live:events:product_impression') ?: 0;
            $clicks = (int) Redis::get('analytics:live:events:product_click') ?: 0;

            // Unique visitors via HyperLogLog
            $uniqueVisitorsToday = 0;
            $uniqueVisitorsThisHour = 0;

            try {
                $uniqueVisitorsToday = (int) Redis::command('PFCOUNT', ["analytics:live:visitors:{$today}"]);
                $uniqueVisitorsThisHour = (int) Redis::command('PFCOUNT', ["analytics:hourly:{$hour}:visitors"]);
            } catch (\Throwable) {
            }

            return response()->json([
                'page_views_total' => $pageViewsTotal,
                'page_views_this_hour' => $pageViewsThisHour,
                'unique_visitors_today' => $uniqueVisitorsToday,
                'unique_visitors_this_hour' => $uniqueVisitorsThisHour,
                'event_pageviews' => $pageviews,
                'event_impressions' => $impressions,
                'event_clicks' => $clicks,
                'last_event_at' => $lastEventAt,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'page_views_total' => 0,
                'page_views_this_hour' => 0,
                'unique_visitors_today' => 0,
                'unique_visitors_this_hour' => 0,
                'event_pageviews' => 0,
                'event_impressions' => 0,
                'event_clicks' => 0,
                'last_event_at' => null,
                'timestamp' => now()->toIso8601String(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Polled by the frontend for the live event feed.
     */
    public function events(Request $request): JsonResponse
    {
        $events = AnalyticsEvent::with('product:id,title')
            ->latest('created_at')
            ->limit($request->get('limit', 30))
            ->get(['id', 'event_name', 'product_id', 'path', 'metadata', 'created_at']);

        return response()->json($events);
    }

    private function getHorizonStats(): array
    {
        try {
            $prefix = config('horizon.prefix', 'horizon:');
            $recentJobs = (int) Redis::get($prefix.'total_processes') ?: 0;
            $failedJobs = (int) Redis::connection('default')->llen($prefix.'failed_jobs') ?: 0;

            return [
                'total_processes' => $recentJobs,
                'failed_jobs' => $failedJobs,
                'status' => 'running',
            ];
        } catch (\Throwable) {
            return [
                'total_processes' => 0,
                'failed_jobs' => 0,
                'status' => 'unknown',
            ];
        }
    }
}
