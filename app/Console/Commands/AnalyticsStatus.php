<?php

namespace App\Console\Commands;

use App\Models\AnalyticsEvent;
use App\Models\AnalyticsReport;
use App\Models\PageView;
use App\Models\Session;
use App\Models\Visitor;
use App\Models\VisitorStat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class AnalyticsStatus extends Command
{
    protected $signature = 'analytics:status';
    protected $description = 'Show current analytics system status, counts, and Redis counters';

    public function handle(): int
    {
        $this->info('📊 Analytics System Status');
        $this->newLine();

        // Database counts
        $this->table(
            ['Table', 'Count'],
            [
                ['visitors', Visitor::count()],
                ['sessions', Session::count()],
                ['page_views', PageView::count()],
                ['analytics_events', AnalyticsEvent::count()],
                ['visitor_stats (aggregated)', VisitorStat::count()],
                ['reports', AnalyticsReport::count()],
            ]
        );

        // Redis counters
        $this->newLine();
        $this->info('Redis Counters (today):');

        try {
            $today = now()->format('Y-m-d');
            $this->table(
                ['Counter', 'Value'],
                [
                    ['pageviews', Redis::get("analytics:counter:pageviews:{$today}") ?? 0],
                    ['sessions', Redis::get("analytics:counter:sessions:{$today}") ?? 0],
                    ['events', Redis::get("analytics:counter:events:{$today}") ?? 0],
                    ['active_visitors', Redis::get('analytics:realtime:active_visitors') ?? 0],
                    ['unique_visitors (HLL)', Redis::command('PFCOUNT', ["analytics:hll:visitors:{$today}"]) ?? 0],
                ]
            );
        } catch (\Throwable $e) {
            $this->warn('Could not read Redis: ' . $e->getMessage());
        }

        // Queue status
        $this->newLine();
        $this->info('Queue lengths:');
        try {
            $prefix = config('database.redis.options.prefix', '');
            $queues = ['default', 'analytics', 'reports'];
            $rows = [];
            foreach ($queues as $queue) {
                $length = Redis::connection('default')->llen("queues:{$queue}");
                $rows[] = [$queue, $length ?? 0];
            }
            $this->table(['Queue', 'Pending Jobs'], $rows);
        } catch (\Throwable $e) {
            $this->warn('Could not read queue lengths: ' . $e->getMessage());
        }

        // Latest report
        $lastReport = AnalyticsReport::latest('generated_at')->first();
        if ($lastReport) {
            $this->newLine();
            $this->info("Latest report: {$lastReport->report_type} generated at {$lastReport->generated_at} ({$lastReport->generation_time_ms}ms)");
        }

        return self::SUCCESS;
    }
}
