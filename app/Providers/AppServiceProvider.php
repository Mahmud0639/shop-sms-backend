<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        //
        // Ngrok প্রক্সির জন্য বাধ্যতামূলকভাবে HTTPS জেনারেট করার নির্দেশ
        URL::forceScheme('https');
    }
}
