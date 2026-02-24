<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrer_stats', function (Blueprint $table) {
            $table->id();
            $table->timestampTz('period'); // hourly bucket
            $table->string('referrer_name')->default('Direct');
            $table->string('referrer', 2048)->nullable();
            $table->unsignedInteger('visitors')->default(0);
            $table->unsignedInteger('sessions')->default(0);
            $table->timestamps();

            $table->unique(['period', 'referrer_name', 'referrer']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrer_stats');
    }
};
