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
        // In production or when accessed via HTTPS tunnel/proxy, generate https links/assets.
        if (
            $this->app->environment('production') ||
            str_starts_with((string) config('app.url'), 'https://') ||
            ($this->app->bound('request') && $this->app->make('request')->header('X-Forwarded-Proto') === 'https')
        ) {
            URL::forceScheme('https');
        }

        // Auto-initialize schema & default users on fresh cloud databases (e.g. Hostinger MySQL / Neon / TiDB)
        $dbReadyMarker = sys_get_temp_dir() . '/.orbit_db_ready_' . md5(base_path()) . '_v4';
        if (! $this->app->runningInConsole() && ! file_exists($dbReadyMarker)) {
            try {
                if (! \Illuminate\Support\Facades\Schema::hasTable('users')) {
                    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
                } elseif (
                    ! \Illuminate\Support\Facades\Schema::hasColumn('documents', 'file_content') ||
                    ! \Illuminate\Support\Facades\Schema::hasColumn('case_checks', 'custom_schema') ||
                    ! \Illuminate\Support\Facades\Schema::hasTable('settings') ||
                    ! \Illuminate\Support\Facades\Schema::hasTable('report_downloads') ||
                    ! \Illuminate\Support\Facades\Schema::hasColumn('case_checks', 'custom_name')
                ) {
                    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                }
                @touch($dbReadyMarker);
            } catch (\Throwable $e) {
                // Database connection not ready yet
            }
        }

        // Apply database-stored SMTP settings if configured
        \App\Models\Setting::applyMailConfig();

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
