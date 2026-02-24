<?php

namespace App\Services;

use App\Models\Session;
use App\Models\Visitor;
use Carbon\Carbon;

class SessionService
{
    private const SESSION_TIMEOUT_MINUTES = 30;

    /**
     * Resolve an existing active session or create a new one.
     *
     * A session is considered active if the last activity was within
     * the timeout window (30 minutes, same as Pirsch/GA).
     */
    public function resolveSession(
        Visitor $visitor,
        string $path,
        ?string $referrer,
        array $deviceInfo,
        ?int $screenWidth,
        ?int $screenHeight,
        ?string $language,
        array $utmParams,
        string $ipHash,
    ): Session {
        $cutoff = Carbon::now()->subMinutes(self::SESSION_TIMEOUT_MINUTES);

        // Look for an active session
        $session = Session::where('visitor_id', $visitor->id)
            ->where('started_at', '>=', $cutoff)
            ->orderByDesc('started_at')
            ->first();

        if ($session) {
            // Update existing session
            $session->update([
                'ended_at' => now(),
                'exit_path' => $path,
                'page_view_count' => $session->page_view_count + 1,
                'duration_seconds' => (int) now()->diffInSeconds($session->started_at),
                'is_bounce' => false, // More than one page view = not a bounce
            ]);

            return $session;
        }

        // Create new session
        $referrerName = $referrer ? app(ReferrerService::class)->parse($referrer) : 'Direct';

        $session = Session::create([
            'visitor_id' => $visitor->id,
            'started_at' => now(),
            'ended_at' => now(),
            'duration_seconds' => 0,
            'is_bounce' => true,
            'entry_path' => $path,
            'exit_path' => $path,
            'page_view_count' => 1,
            'referrer' => $referrer,
            'referrer_name' => $referrerName,
            'utm_source' => $utmParams['utm_source'] ?? null,
            'utm_medium' => $utmParams['utm_medium'] ?? null,
            'utm_campaign' => $utmParams['utm_campaign'] ?? null,
            'utm_term' => $utmParams['utm_term'] ?? null,
            'utm_content' => $utmParams['utm_content'] ?? null,
            'browser' => $deviceInfo['browser'] ?? null,
            'browser_version' => $deviceInfo['browser_version'] ?? null,
            'os' => $deviceInfo['os'] ?? null,
            'os_version' => $deviceInfo['os_version'] ?? null,
            'device_type' => $deviceInfo['device_type'] ?? 'desktop',
            'screen_width' => $screenWidth,
            'screen_height' => $screenHeight,
            'language' => $language,
            'ip_hash' => $ipHash,
        ]);

        // Increment visitor session count
        $visitor->increment('total_sessions');

        return $session;
    }
}
