<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitors', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint', 64)->unique(); // SHA-256 hash of IP+UA+daily salt
            $table->timestampTz('first_seen_at');
            $table->timestampTz('last_seen_at')->index();
            $table->unsignedInteger('total_sessions')->default(0);
            $table->unsignedInteger('total_page_views')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};
