<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use App\Models\AnalyticsReport;
use Carbon\Carbon;

class GenerateDashboardReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;

    public function __construct()
    {
        $this->onQueue('reports');
    }

    public function handle(): void
    {
        $startTime = microtime(true);
        $now = Carbon::now();
        $periodStart = $now->copy()->subDay();
        $periodEnd = $now;

        $batch = Bus::batch([
            new BuildOverviewReport($periodStart->toIso8601String(), $periodEnd->toIso8601String()),
            new BuildTopPagesReport($periodStart->toIso8601String(), $periodEnd->toIso8601String()),
            new BuildTopReferrersReport($periodStart->toIso8601String(), $periodEnd->toIso8601String()),
            new BuildDeviceBreakdownReport($periodStart->toIso8601String(), $periodEnd->toIso8601String()),
            new BuildProductAnalyticsReport($periodStart->toIso8601String(), $periodEnd->toIso8601String()),
        ])
        ->name('Dashboard Report ' . $now->format('Y-m-d H:i'))
        ->onQueue('reports')
        ->then(function (\Illuminate\Bus\Batch $batch) use ($startTime, $periodStart, $periodEnd) {
            $generationTime = (int) ((microtime(true) - $startTime) * 1000);

            // Merge all sub-report results from cache
            $data = [];
            foreach (['overview', 'top_pages', 'top_referrers', 'device_breakdown', 'product_analytics'] as $key) {
                $data[$key] = cache()->get("report_fragment:{$batch->id}:{$key}", []);
                cache()->forget("report_fragment:{$batch->id}:{$key}");
            }

            AnalyticsReport::create([
                'report_type' => 'dashboard_summary',
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'data' => $data,
                'generated_at' => now(),
                'generation_time_ms' => $generationTime,
                'job_batch_id' => $batch->id,
            ]);

            // Dispatch milestone check
            CheckAnalyticsMilestones::dispatch()->onQueue('default');
        })
        ->catch(function (\Illuminate\Bus\Batch $batch, \Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Dashboard report batch failed', [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
            ]);
        })
        ->dispatch();
    }
}
