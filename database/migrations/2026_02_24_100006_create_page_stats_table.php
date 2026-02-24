<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_stats', function (Blueprint $table) {
            $table->id();
            $table->timestampTz('period'); // hourly bucket
            $table->string('path');
            $table->unsignedInteger('visitors')->default(0);
            $table->unsignedInteger('page_views')->default(0);
            $table->unsignedInteger('entries')->default(0);
            $table->unsignedInteger('exits')->default(0);
            $table->unsignedInteger('bounces')->default(0);
            $table->float('avg_duration_ms')->default(0);
            $table->timestamps();

            $table->unique(['period', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_stats');
    }
};
