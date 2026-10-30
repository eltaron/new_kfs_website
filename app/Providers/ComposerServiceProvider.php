<?php

namespace App\Providers;

use Modules\Content\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ComposerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // This composer runs for EVERY rendered view (hundreds per Filament page), so the settings
        // are read from the cache once per request and reused — each cache read is a SQL query
        // with the database cache store (and a Pulse "cache_hit" row).
        $settings = null;

        View::composer('*', function ($view) use (&$settings) {
            $settings ??= Cache::rememberForever('site_settings', function () {
                // Fetch all settings and create an associative array (key => value)
                return Setting::pluck('value', 'key');
            });

            // Share the $settings variable with all views
            $view->with('settings', $settings);
        });
    }
}
