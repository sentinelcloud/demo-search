<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_stats', function (Blueprint $table) {
            $table->id();
            $table->timestampTz('period'); // hourly bucket
            $table->string('browser')->default('Unknown');
            $table->string('os')->default('Unknown');
            $table->string('device_type', 20)->default('desktop');
            $table->unsignedInteger('visitors')->default(0);
            $table->unsignedInteger('sessions')->default(0);
            $table->timestamps();

            $table->unique(['period', 'browser', 'os', 'device_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_stats');
    }
};
