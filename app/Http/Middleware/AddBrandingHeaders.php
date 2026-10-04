<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Injects EthioNext branding into every HTTP response.
 *
 * Layer 2 of the 5-layer branding protection strategy.
 * See config/schoolshare.php for branding configuration.
 */
class AddBrandingHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set(
            'X-Powered-By',
            config('schoolshare.branding.header_value', 'EthioNext-SchoolShare')
        );

        $response->headers->set('X-SchoolShare-Version', '1.0.0');

        return $response;
    }
}
