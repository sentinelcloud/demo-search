<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\AssignAnonymousViewer::class,
        ]);

        // Trust Traefik/Kubernetes proxy — read real client IP from X-Forwarded-For
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR |
                     Request::HEADER_X_FORWARDED_HOST |
                     Request::HEADER_X_FORWARDED_PORT |
                     Request::HEADER_X_FORWARDED_PROTO,
        );

        // CSRF exemption for analytics tracking endpoint
        $middleware->validateCsrfTokens(except: [
            'api/track',
        ]);
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        // Aggregate raw analytics into hourly stats — every 10 minutes
        $schedule->job(\App\Jobs\AggregateVisitorStats::class)->everyTenMinutes();

        // Generate dashboard report batch — every 10 minutes (offset by 3 min)
        $schedule->job(\App\Jobs\GenerateDashboardReport::class)->cron('3,13,23,33,43,53 * * * *')->withoutOverlapping();

        // Warm the analytics dashboard cache — every 10 minutes (offset by 5 min)
        $schedule->job(\App\Jobs\WarmDashboardCache::class)->cron('5,15,25,35,45,55 * * * *');

        // Record metric snapshots for sparklines — every 5 minutes
        $schedule->job(\App\Jobs\RecordAnalyticsSnapshot::class)->everyFiveMinutes();

        // Prune old raw analytics data — daily at 3am
        $schedule->job(\App\Jobs\PruneRawAnalytics::class)->dailyAt('03:00');

        // Shuffle product prices hourly to simulate dynamic pricing
        $schedule->command('products:shuffle-prices')->hourly();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
