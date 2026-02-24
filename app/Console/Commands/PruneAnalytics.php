<?php

namespace App\Console\Commands;

use App\Jobs\PruneRawAnalytics;
use Illuminate\Console\Command;

class PruneAnalytics extends Command
{
    protected $signature = 'analytics:prune';
    protected $description = 'Prune old raw analytics data (page_views 30d, events 30d, sessions 90d, snapshots 30d)';

    public function handle(): int
    {
        $this->info('Dispatching PruneRawAnalytics...');

        PruneRawAnalytics::dispatch()->onQueue('reports');

        $this->info('✓ Prune job dispatched to the reports queue.');
        $this->line('  Retention: page_views=30d, events=30d, sessions=90d, snapshots=30d');

        return self::SUCCESS;
    }
}
