<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Visitor;
use App\Models\PageView;
use App\Notifications\AnalyticsMilestoneNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class CheckAnalyticsMilestones implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const VISITOR_MILESTONE = 1000;
    private const PAGEVIEW_MILESTONE = 10000;

    public function handle(): void
    {
        $totalVisitors = Visitor::count();
        $totalPageViews = PageView::count();

        $lastVisitorMilestone = (int) Cache::get('analytics:milestone:visitors', 0);
        $lastPageViewMilestone = (int) Cache::get('analytics:milestone:page_views', 0);

        $currentVisitorMilestone = (int) floor($totalVisitors / self::VISITOR_MILESTONE) * self::VISITOR_MILESTONE;
        $currentPageViewMilestone = (int) floor($totalPageViews / self::PAGEVIEW_MILESTONE) * self::PAGEVIEW_MILESTONE;

        if ($currentVisitorMilestone > $lastVisitorMilestone && $currentVisitorMilestone > 0) {
            Cache::forever('analytics:milestone:visitors', $currentVisitorMilestone);

            Notification::route('log', 'analytics')
                ->notify(new AnalyticsMilestoneNotification(
                    milestone: "Reached {$currentVisitorMilestone} total visitors!",
                    metric: 'visitors',
                    value: $totalVisitors,
                ));
        }

        if ($currentPageViewMilestone > $lastPageViewMilestone && $currentPageViewMilestone > 0) {
            Cache::forever('analytics:milestone:page_views', $currentPageViewMilestone);

            Notification::route('log', 'analytics')
                ->notify(new AnalyticsMilestoneNotification(
                    milestone: "Reached {$currentPageViewMilestone} total page views!",
                    metric: 'page_views',
                    value: $totalPageViews,
                ));
        }
    }
}
