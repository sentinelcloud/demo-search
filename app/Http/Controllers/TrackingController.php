<?php

namespace App\Http\Controllers;

use App\Events\PageWasViewed;
use App\Events\ProductWasClicked;
use App\Events\ProductWasImpressed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function track(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'events' => 'required|array|max:50',
            'events.*.type' => 'required|string|in:pageview,impression,click',
            'events.*.timestamp' => 'required|string',
            'events.*.path' => 'nullable|string|max:2048',
            'events.*.title' => 'nullable|string|max:512',
            'events.*.referrer' => 'nullable|string|max:2048',
            'events.*.product_id' => 'nullable|integer',
            'events.*.screen_width' => 'nullable|integer',
            'events.*.screen_height' => 'nullable|integer',
            'events.*.language' => 'nullable|string|max:10',
            'events.*.utm_source' => 'nullable|string|max:255',
            'events.*.utm_medium' => 'nullable|string|max:255',
            'events.*.utm_campaign' => 'nullable|string|max:255',
            'events.*.utm_term' => 'nullable|string|max:255',
            'events.*.utm_content' => 'nullable|string|max:255',
            'events.*.metadata' => 'nullable|array',
        ]);

        $ip = $request->ip(); // respects trusted proxies / X-Forwarded-For
        $userAgent = $request->userAgent() ?? 'Unknown';

        foreach ($validated['events'] as $event) {
            match ($event['type']) {
                'pageview' => PageWasViewed::dispatch(
                    $event['path'] ?? '/',
                    $event['title'] ?? null,
                    $event['referrer'] ?? null,
                    $ip,
                    $userAgent,
                    $event['screen_width'] ?? null,
                    $event['screen_height'] ?? null,
                    $event['language'] ?? null,
                    array_filter([
                        'source' => $event['utm_source'] ?? null,
                        'medium' => $event['utm_medium'] ?? null,
                        'campaign' => $event['utm_campaign'] ?? null,
                        'term' => $event['utm_term'] ?? null,
                        'content' => $event['utm_content'] ?? null,
                    ]),
                    $event['timestamp'],
                ),
                'impression' => ProductWasImpressed::dispatch(
                    $event['product_id'],
                    $event['path'] ?? '/',
                    $ip,
                    $userAgent,
                    $event['timestamp'],
                ),
                'click' => ProductWasClicked::dispatch(
                    $event['product_id'],
                    $event['path'] ?? '/',
                    $ip,
                    $userAgent,
                    $event['metadata'] ?? [],
                    $event['timestamp'],
                ),
            };
        }

        return response()->json(['status' => 'ok', 'processed' => count($validated['events'])]);
    }
}
