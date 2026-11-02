<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // In production, generate https links/assets (Hostinger serves over SSL).
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Login: 5 attempts per minute keyed by email + IP
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)
                ->by(strtolower((string) $request->input('email')).'|'.$request->ip())
                ->response(fn () => back()
                    ->withErrors(['email' => 'Too many login attempts. Please wait a minute and try again.'])
                    ->withInput($request->except('password'))
                );
        });

        // Password reset: 3 requests per minute per IP
        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(3)
                ->by($request->ip())
                ->response(fn () => back()
                    ->withErrors(['email' => 'Too many reset requests. Please wait a minute and try again.'])
                );
        });

        // Candidate document upload: 10 per minute per IP
        RateLimiter::for('candidate-upload', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }
}
