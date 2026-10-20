<?php

namespace App\Providers;

use App\Services\AutoUpdateService;
use Desarrolla2\Cache\Adapter\File;
use Throwable;
use Illuminate\Support\ServiceProvider;
use Monolog\Handler\StreamHandler;

class AutoUpdateServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(AutoUpdateService::class, function ($app) {
            $autoUpdateService = new AutoUpdateService(base_path('storage/updates/'), base_path(), 60);
            $autoUpdateService->setCurrentVersion(config('app.version'));
            $autoUpdateService->setUpdateUrl(env('APP_REPOSITORY'));
            $autoUpdateService->setPublicKeys(config('app.update_public_keys'));
            // Admins check for updates on every app load, ask the update server at most once an hour. Without a writable
            // cache directory it simply asks every time, the app info request must not fail because of it.
            try {
                $autoUpdateService->setCache(new File(storage_path('framework/cache/update-check')), 3600);
            } catch (Throwable $e) {
                report($e);
            }
            $autoUpdateService->addLogHandler(new StreamHandler(base_path('storage/logs/update-' . date('Y-m-d') . '.log')));
            return $autoUpdateService;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
