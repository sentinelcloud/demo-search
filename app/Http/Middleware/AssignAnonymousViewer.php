<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Assigns an anonymous session-based identity for broadcasting presence channels.
 *
 * Since this demo app has no authentication, this middleware creates a
 * persistent anonymous viewer identity (stored in session) and sets it
 * as the authenticated user via Auth::setUser(). This allows Laravel's
 * PresenceChannel authorization to work without a real login system.
 */
class AssignAnonymousViewer
{
    /** @var string[] */
    private const ANIMAL_NAMES = [
        'Fox', 'Owl', 'Bear', 'Wolf', 'Hawk', 'Deer', 'Lynx', 'Seal',
        'Crane', 'Raven', 'Otter', 'Panda', 'Tiger', 'Eagle', 'Koala',
        'Moose', 'Whale', 'Bison', 'Falcon', 'Badger',
    ];

    /** @var string[] */
    private const COLORS = [
        '#ef4444', '#f97316', '#eab308', '#22c55e', '#06b6d4',
        '#3b82f6', '#8b5cf6', '#ec4899', '#f43f5e', '#14b8a6',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            $viewerId = $request->session()->get('admin_viewer_id');

            if (! $viewerId) {
                $viewerId = Str::uuid()->toString();
                $viewerName = 'Admin '.self::ANIMAL_NAMES[array_rand(self::ANIMAL_NAMES)];
                $viewerColor = self::COLORS[array_rand(self::COLORS)];

                $request->session()->put('admin_viewer_id', $viewerId);
                $request->session()->put('admin_viewer_name', $viewerName);
                $request->session()->put('admin_viewer_color', $viewerColor);
            }

            Auth::setUser(new GenericUser([
                'id' => abs(crc32($viewerId)),
                'viewer_id' => $request->session()->get('admin_viewer_id'),
                'name' => $request->session()->get('admin_viewer_name'),
                'color' => $request->session()->get('admin_viewer_color'),
            ]));
        }

        return $next($request);
    }
}
