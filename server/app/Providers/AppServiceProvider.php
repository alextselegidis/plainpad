<?php

namespace App\Providers;

use Illuminate\Http\Middleware\TrustProxies;
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
        // Comma separated proxy addresses (or "*") in front of the app, so that request IPs (login rate limit) are the
        // real client addresses instead of the proxy address.
        if ($proxies = env('TRUSTED_PROXIES')) {
            TrustProxies::at($proxies === '*' ? '*' : explode(',', $proxies));
        }
    }
}
