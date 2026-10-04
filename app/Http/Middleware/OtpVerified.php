<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirects unverified users to the OTP verification page.
 * Use instead of the built-in 'verified' middleware.
 */
class OtpVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()->email_verified_at) {
            return redirect()->route('otp.verify.show')
                ->with('warning', 'Please verify your email address to continue.');
        }

        return $next($request);
    }
}
