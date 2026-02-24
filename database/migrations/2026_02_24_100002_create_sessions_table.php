<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('visitor_id')->constrained('visitors')->cascadeOnDelete();
            $table->timestampTz('started_at')->index();
            $table->timestampTz('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->boolean('is_bounce')->default(true);
            $table->string('entry_path')->nullable();
            $table->string('exit_path')->nullable();
            $table->unsignedInteger('page_view_count')->default(0);
            $table->string('referrer', 2048)->nullable();
            $table->string('referrer_name')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('browser')->nullable();
            $table->string('browser_version')->nullable();
            $table->string('os')->nullable();
            $table->string('os_version')->nullable();
            $table->string('device_type', 20)->default('desktop'); // desktop, mobile, tablet, bot
            $table->unsignedSmallInteger('screen_width')->nullable();
            $table->unsignedSmallInteger('screen_height')->nullable();
            $table->string('language', 10)->nullable();
            $table->string('country', 2)->nullable();
            $table->string('ip_hash', 64)->nullable(); // SHA-256 of IP for privacy
            $table->timestamps();

            $table->index(['visitor_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_sessions');
    }
};
