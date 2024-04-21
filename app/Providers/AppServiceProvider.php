<?php

namespace App\Providers;

use App\Events\CoordinatesChanged;
use App\Models\Reference;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Reference::preventSilentlyDiscardingAttributes($this->app->isLocal());

        Event::listen(
            CoordinatesChanged::class,
        );
    }
}
