<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\EventStat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AggregateEventStats implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $periodIso)
    {
        $this->onQueue('reports');
    }

    public function handle(): void
    {
        $period = Carbon::parse($this->periodIso)->startOfHour();
        $periodEnd = $period->copy()->endOfHour();

        $events = DB::table('analytics_events')
            ->whereBetween('created_at', [$period, $periodEnd])
            ->select('event_name', 'product_id')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('COUNT(DISTINCT visitor_id) as unique_visitors')
            ->groupBy('event_name', 'product_id')
            ->get();

        foreach ($events as $event) {
            EventStat::updateOrCreate(
                [
                    'period' => $period,
                    'event_name' => $event->event_name,
                    'product_id' => $event->product_id,
                ],
                [
                    'count' => $event->count,
                    'unique_visitors' => $event->unique_visitors,
                ]
            );
        }
    }
}
