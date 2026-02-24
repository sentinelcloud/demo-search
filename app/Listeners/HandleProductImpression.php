<?php

namespace App\Listeners;

use App\Events\ProductWasImpressed;
use App\Jobs\ProcessAnalyticsEvent;

class HandleProductImpression
{
    public function handle(ProductWasImpressed $event): void
    {
        ProcessAnalyticsEvent::dispatch(
            'product_impression',
            $event->productId,
            $event->path,
            [],
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
