<?php

namespace App\Providers;

use App\Models\Session;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Auth::viaRequest('custom-token', function ($request) {
            $session = Session::where('token', $request->bearerToken())
                ->where('expires_at', '>', DB::raw('NOW()'))
                ->first();

            if (!$session) {
                return null;
            }

            $user = User::find($session->user_id);

            if (!$user) {
                return null;
            }

            config(['app.locale' => $user->locale]);

            return $user;
        });
    }
}
