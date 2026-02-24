<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnalyticsEventProcessed implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array{
     *     id: int,
     *     event_name: string,
     *     product_id: int|null,
     *     path: string|null,
     *     created_at: string,
     *     product: array{id: int, title: string}|null,
     * }  $event
     */
    public function __construct(
        public array $event,
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
        return 'event.processed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return $this->event;
    }
}
