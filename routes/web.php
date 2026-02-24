<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AnalyticsDashboardController;
use App\Http\Controllers\AnalyticsReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

// Search
Route::get('/', [SearchController::class, 'index'])->name('search');

// Tracking API (CSRF-exempt, see bootstrap/app.php)
Route::post('/api/track', [TrackingController::class, 'track'])->name('api.track');

// Admin CRUD
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::get('/create', [AdminController::class, 'create'])->name('create');
    Route::post('/', [AdminController::class, 'store'])->name('store');
    Route::get('/{product}/edit', [AdminController::class, 'edit'])->name('edit');
    Route::put('/{product}', [AdminController::class, 'update'])->name('update');
    Route::delete('/{product}', [AdminController::class, 'destroy'])->name('destroy');

    // Realtime analytics dashboard
    Route::get('/analytics', [AnalyticsDashboardController::class, 'index'])->name('analytics');
    Route::get('/analytics/live', [AnalyticsDashboardController::class, 'live'])->name('analytics.live');
    Route::get('/analytics/events', [AnalyticsDashboardController::class, 'events'])->name('analytics.events');

    // Static aggregated reports
    Route::get('/reports', [AnalyticsReportController::class, 'index'])->name('reports');
});

