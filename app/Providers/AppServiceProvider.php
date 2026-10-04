<?php

namespace App\Providers;

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
        \Illuminate\Pagination\Paginator::useBootstrapFive();

        // Layer 3: Boot-time license check
        if (config('schoolshare.branding.mode') === 'whitelabel') {
            $key = config('schoolshare.branding.license_key');
            if (empty($key) || !str_starts_with($key, 'SS-')) {
                abort(503, 'SchoolShare Error: White-label mode requires a valid Commercial License key. Please check your .env file or revert to community mode.');
            }
        }

        // Configure Rate Limiting for Uploads
        \Illuminate\Support\Facades\RateLimiter::for('uploads', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });
    }
}
