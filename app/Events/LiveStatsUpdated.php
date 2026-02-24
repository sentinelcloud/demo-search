<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LiveStatsUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array{
     *     page_views_total: int,
     *     page_views_this_hour: int,
     *     unique_visitors_today: int,
     *     unique_visitors_this_hour: int,
     *     event_pageviews: int,
     *     event_impressions: int,
     *     event_clicks: int,
     *     last_event_at: string|null,
     *     timestamp: string,
     * }  $stats
     */
    public function __construct(
        public array $stats,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('analytics.dashboard'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'stats.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return $this->stats;
    }
}
