<?php

namespace App\Http\Middleware;

use App\SchoolShareInstaller;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InstallationAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $installer = app(SchoolShareInstaller::class);
        abort_unless(config('installation.enabled') && ! $installer->locked() && preg_match('/\A[a-f0-9]{64}\z/', (string) config('installation.token_hash')), 404);
        if (config('installation.require_https') && ! $request->isSecure()) {
            return response('Enable HTTPS before opening SchoolShare setup.', 403)->header('Cache-Control', 'no-store');
        }
        if ($request->routeIs('installation.store')) {
            abort_unless(hash_equals((string) config('installation.token_hash'), (string) $request->session()->get('installation.authorization', ''))
                && (int) $request->session()->get('installation.expires_at', 0) > time(), 403, 'Unlock setup with the private token first.');
        }

        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
