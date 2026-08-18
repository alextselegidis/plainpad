<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(200)->by($request->user()?->id ?: $request->ip());
        });

        // Login attempts are keyed on the target account first, so that credential stuffing cannot be
        // spread across rotating source addresses. The IP limit is deliberately loose because instances
        // behind a reverse proxy see every client as the proxy address.
        RateLimiter::for('login', function (Request $request) {
            $email = Str::lower((string)$request->input('email'));

            return [
                Limit::perMinutes(15, 5)->by('login-email:' . $email),
                Limit::perMinutes(15, 50)->by('login-ip:' . $request->ip()),
            ];
        });
    }
}
