<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PageWasViewed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $path,
        public ?string $title,
        public ?string $referrer,
        public string $ip,
        public string $userAgent,
        public ?int $screenWidth,
        public ?int $screenHeight,
        public ?string $language,
        public array $utmParams,
        public string $timestamp,
    ) {
    }
}
