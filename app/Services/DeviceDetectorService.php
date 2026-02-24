<?php

namespace App\Services;

use DeviceDetector\DeviceDetector;
use Illuminate\Support\Facades\Cache;

class DeviceDetectorService
{
    /**
     * Parse a user agent string into device information.
     *
     * Results are cached by UA hash in Redis to avoid re-parsing
     * the same user agent string repeatedly.
     */
    public function parse(string $userAgent): array
    {
        $cacheKey = 'device:' . md5($userAgent);

        return Cache::tags(['device-detection'])->remember($cacheKey, 86400, function () use ($userAgent) {
            $dd = new DeviceDetector($userAgent);
            $dd->parse();

            $client = $dd->getClient();
            $os = $dd->getOs();

            // Determine device type
            $deviceType = 'desktop';
            if ($dd->isBot()) {
                $deviceType = 'bot';
            } elseif ($dd->isTablet()) {
                $deviceType = 'tablet';
            } elseif ($dd->isMobile()) {
                $deviceType = 'mobile';
            }

            return [
                'browser' => is_array($client) ? ($client['name'] ?? 'Unknown') : 'Unknown',
                'browser_version' => is_array($client) ? ($client['version'] ?? '') : '',
                'os' => is_array($os) ? ($os['name'] ?? 'Unknown') : 'Unknown',
                'os_version' => is_array($os) ? ($os['version'] ?? '') : '',
                'device_type' => $deviceType,
                'is_bot' => $dd->isBot(),
            ];
        });
    }
}
