<?php

use Illuminate\Support\Facades\Broadcast;

// Analytics dashboard — public channel (no auth required)
// Events: stats.updated, event.processed
// Used by: Admin/Analytics.tsx via Laravel Echo
Broadcast::channel('analytics.dashboard', function () {
    return true;
});

// Analytics dashboard — presence channel (anonymous viewers)
// The AssignAnonymousViewer middleware assigns a session-based identity
// so presence channels work without a traditional auth system.
// Returns viewer info: id, name, color (for avatar rendering)
Broadcast::channel('analytics.viewers', function ($user) {
    return [
        'id' => $user->viewer_id ?? $user->getAuthIdentifier() ?? 'anonymous',
        'name' => $user->name ?? 'Anonymous',
        'color' => $user->color ?? '#3b82f6',
    ];
});
