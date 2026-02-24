<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ReferrerStat;
use Carbon\Carbon;

class BuildTopReferrersReport implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $periodStart, public string $periodEnd)
    {
        $this->onQueue('reports');
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $start = Carbon::parse($this->periodStart);
        $end = Carbon::parse($this->periodEnd);

        $referrers = ReferrerStat::whereBetween('period', [$start, $end])
            ->select('referrer_name')
            ->selectRaw('SUM(visitors) as total_visitors')
            ->selectRaw('SUM(sessions) as total_sessions')
            ->groupBy('referrer_name')
            ->orderByDesc('total_visitors')
            ->limit(20)
            ->get()
            ->toArray();

        cache()->put("report_fragment:{$this->batch()->id}:top_referrers", $referrers, 600);
    }
}
