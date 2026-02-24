<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class ShuffleProductPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:shuffle-prices
                            {--min-percent=5 : Minimum percentage change}
                            {--max-percent=20 : Maximum percentage change}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Randomly adjust product prices within a percentage range and re-sync to Meilisearch';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $minPercent = (int) $this->option('min-percent');
        $maxPercent = (int) $this->option('max-percent');

        $total = Product::count();

        if ($total === 0) {
            $this->warn('No products found.');

            return self::SUCCESS;
        }

        $this->info("Shuffling prices for {$total} products (±{$minPercent}–{$maxPercent}%)...");

        // Use raw SQL for fast bulk update — random price change within range
        // Formula: price * (1 + random_direction * random_percent / 100)
        // random() in PostgreSQL returns [0, 1), so we map it to our range
        $range = $maxPercent - $minPercent;

        Product::query()->update([
            'price' => \Illuminate\Support\Facades\DB::raw(
                "GREATEST(0.99, ROUND(CAST(price * (1 + (CASE WHEN random() > 0.5 THEN 1 ELSE -1 END) * ({$minPercent} + random() * {$range}) / 100) AS numeric), 2))"
            ),
        ]);

        $this->info('Prices updated in database. Syncing to Meilisearch...');

        // Re-sync all products to Meilisearch in batches via Scout
        $this->call('scout:import', ['model' => Product::class]);

        $this->info("Done! {$total} product prices shuffled and synced.");

        return self::SUCCESS;
    }
}
