<?php

use App\Http\Controllers\InstallationController;
use App\Http\Controllers\ReadinessController;
use App\Http\Middleware\AddBrandingHeaders;
use App\Http\Middleware\InstallationAccess;
use App\Http\Middleware\IsAdmin;
use App\Http\Middleware\OtpVerified;
use App\Http\Middleware\RequireInstallation;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware(['web', InstallationAccess::class])->group(function (): void {
                Route::get('/install', [InstallationController::class, 'show'])->name('installation.show');
                Route::post('/install/unlock', [InstallationController::class, 'unlock'])->middleware('throttle:5,1')->name('installation.unlock');
                Route::post('/install', [InstallationController::class, 'store'])->middleware('throttle:5,1')->name('installation.store');
            });
            Route::get('/ready', ReadinessController::class)->name('readiness');

            Route::middleware(['web', 'auth', 'otp.verified', 'is_admin'])
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trimStrings(except: ['db_password', 'mail_password', 'admin_password', 'admin_password_confirmation']);
        $middleware->prepend(RequireInstallation::class);
        $middleware->append(AddBrandingHeaders::class);
        $middleware->alias([
            'otp.verified' => OtpVerified::class,
            'is_admin' => IsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['otp', 'setup_token', 'db_password', 'mail_password', 'admin_password', 'admin_password_confirmation']);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

if (is_file(dirname(__DIR__).'/.cpanel-layout')) {
    $app->usePublicPath(dirname(dirname(__DIR__)).'/public_html');
}

return $app;
