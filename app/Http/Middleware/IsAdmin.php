<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts access to admin-only routes.
 * Users must have is_admin = true on their record.
 */
class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() || ! auth()->user()->is_admin) {
            abort(403, 'Access denied. This area is for administrators only.');
        }

        return $next($request);
    }
}
