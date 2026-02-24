<?php

namespace App\Services;

use App\Models\Visitor;
use Illuminate\Support\Facades\Cache;

class FingerprintService
{
    /**
     * Generate a privacy-friendly visitor fingerprint.
     *
     * Uses a daily rotating salt so the same visitor gets a consistent
     * fingerprint within a day but different ones across days.
     * This is the same approach used by Pirsch analytics.
     */
    public function generateFingerprint(string $ip, string $userAgent): string
    {
        $salt = $this->getDailySalt();

        return hash('sha256', $ip . $userAgent . $salt);
    }

    /**
     * Resolve or create a visitor from IP + User-Agent.
     */
    public function resolveVisitor(string $ip, string $userAgent): Visitor
    {
        $fingerprint = $this->generateFingerprint($ip, $userAgent);

        return Visitor::firstOrCreate(
            ['fingerprint' => $fingerprint],
            [
                'first_seen_at' => now(),
                'last_seen_at' => now(),
                'total_sessions' => 0,
                'total_page_views' => 0,
            ]
        );
    }

    /**
     * Get or generate a daily rotating salt.
     * Stored in Redis cache, rotates automatically each day.
     */
    private function getDailySalt(): string
    {
        $key = 'analytics:daily_salt:' . now()->format('Y-m-d');

        return Cache::remember($key, now()->endOfDay(), function () {
            return bin2hex(random_bytes(32));
        });
    }
}
