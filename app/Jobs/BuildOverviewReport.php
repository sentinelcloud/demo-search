<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\VisitorStat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BuildOverviewReport implements ShouldQueue
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

        $totals = VisitorStat::whereBetween('period', [$start, $end])
            ->selectRaw('SUM(visitors) as visitors')
            ->selectRaw('SUM(new_visitors) as new_visitors')
            ->selectRaw('SUM(sessions) as sessions')
            ->selectRaw('SUM(page_views) as page_views')
            ->selectRaw('SUM(bounces) as bounces')
            ->selectRaw('AVG(avg_duration_seconds) as avg_duration')
            ->first();

        // Previous period for comparison
        $prevStart = $start->copy()->sub($end->diff($start));
        $prevTotals = VisitorStat::whereBetween('period', [$prevStart, $start])
            ->selectRaw('SUM(visitors) as visitors')
            ->selectRaw('SUM(sessions) as sessions')
            ->selectRaw('SUM(page_views) as page_views')
            ->selectRaw('SUM(bounces) as bounces')
            ->first();

        $bounceRate = ($totals->sessions ?? 0) > 0
            ? round(($totals->bounces ?? 0) / $totals->sessions * 100, 1)
            : 0;

        $prevBounceRate = ($prevTotals->sessions ?? 0) > 0
            ? round(($prevTotals->bounces ?? 0) / $prevTotals->sessions * 100, 1)
            : 0;

        $data = [
            'visitors' => (int) ($totals->visitors ?? 0),
            'new_visitors' => (int) ($totals->new_visitors ?? 0),
            'sessions' => (int) ($totals->sessions ?? 0),
            'page_views' => (int) ($totals->page_views ?? 0),
            'bounce_rate' => $bounceRate,
            'avg_duration' => round($totals->avg_duration ?? 0, 1),
            'prev_visitors' => (int) ($prevTotals->visitors ?? 0),
            'prev_sessions' => (int) ($prevTotals->sessions ?? 0),
            'prev_page_views' => (int) ($prevTotals->page_views ?? 0),
            'prev_bounce_rate' => $prevBounceRate,
        ];

        cache()->put("report_fragment:{$this->batch()->id}:overview", $data, 600);
    }
}
