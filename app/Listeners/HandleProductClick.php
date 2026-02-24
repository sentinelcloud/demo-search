<?php

namespace App\Listeners;

use App\Events\ProductWasClicked;
use App\Jobs\ProcessAnalyticsEvent;

class HandleProductClick
{
    public function handle(ProductWasClicked $event): void
    {
        ProcessAnalyticsEvent::dispatch(
            'product_click',
            $event->productId,
            $event->path,
            $event->metadata,
            $event->ip,
            $event->userAgent,
            null,
            null,
            null,
            [],
            $event->timestamp,
        )->onQueue('analytics');
    }
}
