<?php

namespace App\Http\Middleware;

use App\SchoolShareInstaller;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireInstallation
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (config('installation.enabled') && ! app(SchoolShareInstaller::class)->locked() && ! $request->is('install', 'install/*')) {
            if ($request->is('up', 'ready') || ! $request->isMethod('GET')) {
                return response('Installation required.', 503)->header('Cache-Control', 'no-store');
            }

            return redirect('/install');
        }

        return $next($request);
    }
}
