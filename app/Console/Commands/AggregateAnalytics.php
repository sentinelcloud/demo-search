<?php

namespace App\Console\Commands;

use App\Jobs\AggregateVisitorStats;
use Illuminate\Console\Command;

class AggregateAnalytics extends Command
{
    protected $signature = 'analytics:aggregate';
    protected $description = 'Dispatch the analytics aggregation job chain (visitor → page → referrer → device → event stats)';

    public function handle(): int
    {
        $this->info('Dispatching AggregateVisitorStats (chains to all sub-aggregations)...');

        AggregateVisitorStats::dispatch()->onQueue('analytics');

        $this->info('✓ Aggregation jobs dispatched to the analytics queue.');
        $this->line('  Run `php artisan horizon` or check the Horizon dashboard to monitor progress.');

        return self::SUCCESS;
    }
}
