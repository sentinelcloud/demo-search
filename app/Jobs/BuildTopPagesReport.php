<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\PageStat;
use Carbon\Carbon;

class BuildTopPagesReport implements ShouldQueue
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

        $pages = PageStat::whereBetween('period', [$start, $end])
            ->select('path')
            ->selectRaw('SUM(page_views) as total_views')
            ->selectRaw('SUM(visitors) as total_visitors')
            ->selectRaw('SUM(entries) as total_entries')
            ->selectRaw('SUM(exits) as total_exits')
            ->selectRaw('AVG(avg_duration_ms) as avg_duration')
            ->groupBy('path')
            ->orderByDesc('total_views')
            ->limit(20)
            ->get()
            ->toArray();

        cache()->put("report_fragment:{$this->batch()->id}:top_pages", $pages, 600);
    }
}
