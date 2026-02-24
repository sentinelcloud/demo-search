<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_id')->constrained('visitors')->cascadeOnDelete();
            $table->uuid('session_id');
            $table->string('path');
            $table->string('title')->nullable();
            $table->string('referrer', 2048)->nullable();
            $table->unsignedInteger('duration_ms')->nullable(); // populated on next navigation
            $table->timestampTz('created_at')->index();

            $table->foreign('session_id')->references('id')->on('analytics_sessions')->cascadeOnDelete();
            $table->index(['session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
