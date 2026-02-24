<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_stats', function (Blueprint $table) {
            $table->id();
            $table->timestampTz('period'); // hourly bucket
            $table->string('event_name');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->unsignedInteger('count')->default(0);
            $table->unsignedInteger('unique_visitors')->default(0);
            $table->timestamps();

            $table->unique(['period', 'event_name', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_stats');
    }
};
