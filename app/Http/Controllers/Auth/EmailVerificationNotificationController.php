<?php

namespace App\Http\Controllers\Auth;

use App\EmailOtpService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request, EmailOtpService $otp): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $sent = $otp->send($request->user());

        return redirect()->route('otp.verify.show')->with(
            $sent ? 'success' : 'warning',
            $sent ? 'A new verification code has been sent to your email.' : 'Email delivery failed. Please use Resend code to try again.'
        );
    }
}
