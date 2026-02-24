<?php

namespace App\Console\Commands;

use App\Jobs\GenerateDashboardReport;
use Illuminate\Console\Command;

class GenerateReport extends Command
{
    protected $signature = 'analytics:report {--period=24h : Period to report on (1h, 24h, 7d, 30d)}';
    protected $description = 'Dispatch dashboard report generation (Bus::batch of 5 sub-reports)';

    public function handle(): int
    {
        $period = $this->option('period');
        $this->info("Dispatching GenerateDashboardReport for period: {$period}...");

        GenerateDashboardReport::dispatch()->onQueue('reports');

        $this->info('✓ Report generation batch dispatched to the reports queue.');
        $this->line('  Sub-reports: Overview, Top Pages, Referrers, Devices, Product Analytics');
        $this->line('  Monitor batch progress in the Horizon dashboard.');

        return self::SUCCESS;
    }
}
