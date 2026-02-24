<?php

namespace App\Services;

class ReferrerService
{
    /**
     * Known referrer mappings: domain pattern => human-readable name.
     */
    private const KNOWN_REFERRERS = [
        'google' => 'Google',
        'bing' => 'Bing',
        'yahoo' => 'Yahoo',
        'duckduckgo' => 'DuckDuckGo',
        'baidu' => 'Baidu',
        'yandex' => 'Yandex',
        'ecosia' => 'Ecosia',
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'twitter' => 'Twitter',
        'x.com' => 'Twitter/X',
        't.co' => 'Twitter/X',
        'linkedin' => 'LinkedIn',
        'reddit' => 'Reddit',
        'pinterest' => 'Pinterest',
        'tiktok' => 'TikTok',
        'youtube' => 'YouTube',
        'github' => 'GitHub',
        'stackoverflow' => 'Stack Overflow',
        'medium' => 'Medium',
        'substack' => 'Substack',
        'hackernews' => 'Hacker News',
        'news.ycombinator' => 'Hacker News',
        'producthunt' => 'Product Hunt',
        'slack' => 'Slack',
        'discord' => 'Discord',
        'whatsapp' => 'WhatsApp',
        'telegram' => 'Telegram',
        'outlook' => 'Email (Outlook)',
        'mail.google' => 'Email (Gmail)',
    ];

    /**
     * Parse a referrer URL into a human-readable source name.
     */
    public function parse(?string $referrer): string
    {
        if (empty($referrer)) {
            return 'Direct';
        }

        $host = parse_url($referrer, PHP_URL_HOST);
        if (!$host) {
            return 'Direct';
        }

        $host = strtolower($host);

        // Strip www. prefix
        $host = preg_replace('/^www\./', '', $host);

        // Check against known referrers
        foreach (self::KNOWN_REFERRERS as $pattern => $name) {
            if (str_contains($host, $pattern)) {
                return $name;
            }
        }

        // Fallback: extract top-level domain as name
        // e.g. "blog.example.com" → "example.com"
        $parts = explode('.', $host);
        if (count($parts) >= 2) {
            return implode('.', array_slice($parts, -2));
        }

        return $host;
    }
}
