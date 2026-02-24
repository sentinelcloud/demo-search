<?php

namespace App\Providers;

use App\Events\PageWasViewed;
use App\Events\ProductWasClicked;
use App\Events\ProductWasImpressed;
use App\Listeners\HandlePageView;
use App\Listeners\HandleProductClick;
use App\Listeners\HandleProductImpression;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Meilisearch\Client;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Client::class, function () {
            return new Client(
                config('scout.meilisearch.host'),
                config('scout.meilisearch.key'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(PageWasViewed::class, HandlePageView::class);
        Event::listen(ProductWasImpressed::class, HandleProductImpression::class);
        Event::listen(ProductWasClicked::class, HandleProductClick::class);
    }
}
