<?php

namespace App\Listeners;

use App\Events\PageWasViewed;
use App\Jobs\ProcessPageView;

class HandlePageView
{
    public function handle(PageWasViewed $event): void
    {
        ProcessPageView::dispatch(
            $event->path,
            $event->title,
            $event->referrer,
            $event->ip,
            $event->userAgent,
            $event->screenWidth,
            $event->screenHeight,
            $event->language,
            $event->utmParams,
            $event->timestamp,
        )->onQueue('analytics');
    }
}
