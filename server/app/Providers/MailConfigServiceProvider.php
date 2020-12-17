<?php


namespace App\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class MailConfigServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        if (!File::exists(base_path('.env'))) {
            return;
        }

        if (!Schema::hasTable('settings')) {
            return;
        }

        $settings = DB::table('settings')
            ->where('name', 'like', 'mail_%')
            ->get()
            ->mapWithKeys(function ($setting) {
                return [$setting->name => $setting->value];
            });

        if ($settings->isEmpty()) {
            return;
        }

        $config = [
            'driver' => $settings['mail_driver'],
            'host' => $settings['mail_host'],
            'port' => $settings['mail_port'],
            'encryption' => $settings['mail_encryption'],
            'username' => $settings['mail_username'],
            'password' => $settings['mail_password'],
            'from' => [
                'address' => $settings['mail_from_address'],
                'name' => $settings['mail_from_name']
            ]
        ];

        Config::set('mail', $config);
    }
}
