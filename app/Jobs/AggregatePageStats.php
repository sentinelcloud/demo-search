<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\PageStat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AggregatePageStats implements ShouldQueue
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

        $pages = DB::table('page_views')
            ->whereBetween('created_at', [$period, $periodEnd])
            ->select('path')
            ->selectRaw('COUNT(*) as page_views')
            ->selectRaw('COUNT(DISTINCT visitor_id) as visitors')
            ->selectRaw('AVG(duration_ms) as avg_duration_ms')
            ->groupBy('path')
            ->get();

        foreach ($pages as $page) {
            // Count entries (first page view in a session)
            $entries = DB::table('page_views as pv')
                ->join(
                    DB::raw('(SELECT session_id, MIN(created_at) as first_at FROM page_views WHERE created_at BETWEEN ? AND ? GROUP BY session_id) as first_pvs'),
                    function ($join) {
                        $join->on('pv.session_id', '=', 'first_pvs.session_id')
                             ->on('pv.created_at', '=', 'first_pvs.first_at');
                    }
                )
                ->addBinding([$period, $periodEnd], 'join')
                ->where('pv.path', $page->path)
                ->count();

            // Count exits (last page view in a session)
            $exits = DB::table('page_views as pv')
                ->join(
                    DB::raw('(SELECT session_id, MAX(created_at) as last_at FROM page_views WHERE created_at BETWEEN ? AND ? GROUP BY session_id) as last_pvs'),
                    function ($join) {
                        $join->on('pv.session_id', '=', 'last_pvs.session_id')
                             ->on('pv.created_at', '=', 'last_pvs.last_at');
                    }
                )
                ->addBinding([$period, $periodEnd], 'join')
                ->where('pv.path', $page->path)
                ->count();

            PageStat::updateOrCreate(
                ['period' => $period, 'path' => $page->path],
                [
                    'visitors' => $page->visitors,
                    'page_views' => $page->page_views,
                    'entries' => $entries,
                    'exits' => $exits,
                    'bounces' => 0, // simplified
                    'avg_duration_ms' => round($page->avg_duration_ms ?? 0, 2),
                ]
            );
        }
    }
}
