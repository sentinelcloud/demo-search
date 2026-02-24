<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\EventStat;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BuildProductAnalyticsReport implements ShouldQueue
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

        // Top products by impressions
        $impressions = EventStat::whereBetween('period', [$start, $end])
            ->where('event_name', 'impression')
            ->whereNotNull('product_id')
            ->select('product_id')
            ->selectRaw('SUM(count) as total_impressions')
            ->selectRaw('SUM(unique_visitors) as unique_viewers')
            ->groupBy('product_id')
            ->orderByDesc('total_impressions')
            ->limit(20)
            ->get();

        $clicks = EventStat::whereBetween('period', [$start, $end])
            ->where('event_name', 'click')
            ->whereNotNull('product_id')
            ->select('product_id')
            ->selectRaw('SUM(count) as total_clicks')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $productIds = $impressions->pluck('product_id')->toArray();
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        $topProducts = $impressions->map(function ($imp) use ($clicks, $products) {
            $clickCount = $clicks->get($imp->product_id)?->total_clicks ?? 0;
            $product = $products->get($imp->product_id);
            $ctr = $imp->total_impressions > 0
                ? round($clickCount / $imp->total_impressions * 100, 2)
                : 0;

            return [
                'product_id' => $imp->product_id,
                'title' => $product?->title ?? 'Unknown',
                'category' => $product?->category ?? 'Unknown',
                'impressions' => (int) $imp->total_impressions,
                'unique_viewers' => (int) $imp->unique_viewers,
                'clicks' => (int) $clickCount,
                'ctr' => $ctr,
            ];
        })->toArray();

        // Event totals
        $eventTotals = EventStat::whereBetween('period', [$start, $end])
            ->select('event_name')
            ->selectRaw('SUM(count) as total')
            ->selectRaw('SUM(unique_visitors) as unique_total')
            ->groupBy('event_name')
            ->get()
            ->keyBy('event_name')
            ->toArray();

        $data = [
            'top_products' => $topProducts,
            'event_totals' => $eventTotals,
        ];

        cache()->put("report_fragment:{$this->batch()->id}:product_analytics", $data, 600);
    }
}
