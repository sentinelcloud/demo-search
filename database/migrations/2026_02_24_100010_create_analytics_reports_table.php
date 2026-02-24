<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_type'); // dashboard_summary, top_products, etc.
            $table->timestampTz('period_start');
            $table->timestampTz('period_end');
            $table->jsonb('data'); // full report payload
            $table->timestampTz('generated_at');
            $table->unsignedInteger('generation_time_ms')->default(0);
            $table->string('job_batch_id')->nullable(); // links to job_batches table
            $table->timestamps();

            $table->index(['report_type', 'generated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_reports');
    }
};
