<?php

namespace App\Providers;

use App\Services\AutoUpdateService;
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
            $autoUpdateService->setSslVerifyHost(false);
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
