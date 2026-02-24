<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_id')->constrained('visitors')->cascadeOnDelete();
            $table->uuid('session_id');
            $table->string('event_name')->index(); // impression, click, etc.
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('path')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestampTz('created_at')->index();

            $table->foreign('session_id')->references('id')->on('analytics_sessions')->cascadeOnDelete();
            $table->index(['event_name', 'product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
