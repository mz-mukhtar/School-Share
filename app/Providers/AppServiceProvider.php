<?php

namespace App\Providers;

use App\EmailOtpService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
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
        Paginator::useBootstrapFive();

        RateLimiter::for('otp-verify', fn (Request $request) => [
            Limit::perMinutes(EmailOtpService::WINDOW_MINUTES, 10)->by('account:'.$request->user()->id),
            Limit::perMinutes(EmailOtpService::WINDOW_MINUTES, 30)->by('source:'.$request->ip()),
        ]);
        RateLimiter::for('otp-send', fn (Request $request) => [
            Limit::perMinutes(EmailOtpService::WINDOW_MINUTES, EmailOtpService::MAX_SENDS)->by('account:'.$request->user()->id),
            Limit::perMinutes(EmailOtpService::WINDOW_MINUTES, 10)->by('source:'.$request->ip()),
        ]);
        RateLimiter::for('registration', fn (Request $request) => Limit::perMinutes(10, 5)->by($request->ip()));

        // Layer 3: Boot-time license check
        if (config('schoolshare.branding.mode') === 'whitelabel') {
            $key = config('schoolshare.branding.license_key');
            if (empty($key) || ! str_starts_with($key, 'SS-')) {
                abort(503, 'SchoolShare Error: White-label mode requires a valid Commercial License key. Please check your .env file or revert to community mode.');
            }
        }

        // Configure Rate Limiting for Uploads
        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(config('schoolshare.rate_limits.upload_per_minute'))->by($request->user()?->id ?: $request->ip());
        });
        foreach (['downloads' => 'download_per_minute', 'file-views' => 'file_view_per_minute', 'editor' => 'editor_per_minute', 'diffs' => 'diff_per_minute', 'forks' => 'fork_per_minute'] as $name => $setting) {
            RateLimiter::for($name, fn (Request $request) => Limit::perMinute(config('schoolshare.rate_limits.'.$setting))->by($request->user()?->id ?: $request->ip()));
        }
    }
}
