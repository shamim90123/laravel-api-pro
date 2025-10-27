<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [];
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        ResetPassword::createUrlUsing(function ($user, string $token) {
            $base = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));
            $query = http_build_query(['token' => $token, 'email' => $user->email]);
            return "{$base}/reset-password?{$query}";
        });
    }
}
