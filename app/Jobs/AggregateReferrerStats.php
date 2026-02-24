<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ReferrerStat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AggregateReferrerStats implements ShouldQueue
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

        $referrers = DB::table('analytics_sessions')
            ->whereBetween('started_at', [$period, $periodEnd])
            ->select('referrer_name', 'referrer')
            ->selectRaw('COUNT(DISTINCT visitor_id) as visitors')
            ->selectRaw('COUNT(*) as sessions')
            ->groupBy('referrer_name', 'referrer')
            ->get();

        foreach ($referrers as $ref) {
            ReferrerStat::updateOrCreate(
                [
                    'period' => $period,
                    'referrer_name' => $ref->referrer_name ?? 'Direct',
                    'referrer' => $ref->referrer,
                ],
                [
                    'visitors' => $ref->visitors,
                    'sessions' => $ref->sessions,
                ]
            );
        }
    }
}
