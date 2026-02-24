<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::withoutSyncingToSearch(function () {
            Product::factory()->count(200000)->create();
        });

        $this->command->info('Created 200,000 products. Run `php artisan scout:import "App\\Models\\Product"` to index them.');
    }
}
