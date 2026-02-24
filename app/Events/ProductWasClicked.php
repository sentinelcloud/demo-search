<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductWasClicked
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $productId,
        public string $path,
        public string $ip,
        public string $userAgent,
        public array $metadata,
        public string $timestamp,
    ) {
    }
}
