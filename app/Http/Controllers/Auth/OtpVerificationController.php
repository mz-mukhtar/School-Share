<?php

namespace App\Http\Controllers\Auth;

use App\EmailOtpService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OtpVerificationController extends Controller
{
    /**
     * Show the OTP entry form.
     */
    public function show(Request $request): View|RedirectResponse
    {
        // If already verified, go to dashboard
        if ($request->user()->email_verified_at) {
            return redirect()->route('dashboard');
        }

        return view('auth.verify-otp', [
            'email' => $request->user()->email,
        ]);
    }

    /**
     * Verify the submitted OTP.
     */
    public function verify(Request $request, EmailOtpService $otp): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'digits:6'],
        ]);

        $error = $otp->verify($request->user(), $request->string('otp')->toString());
        if ($error !== null) {
            return back()->withErrors(['otp' => $error]);
        }

        return redirect()->route('dashboard')
            ->with('success', '🎉 Email verified! Welcome to SchoolShare.');
    }

    /**
     * Resend a fresh OTP to the user's email.
     */
    public function resend(Request $request, EmailOtpService $otp): RedirectResponse
    {
        $user = $request->user();

        if ($user->email_verified_at) {
            return redirect()->route('dashboard');
        }

        if (! $otp->send($user)) {
            return back()->with('warning', 'Email delivery failed. Please use Resend code to try again.');
        }

        return back()->with('success', 'A new verification code has been sent to your email.');
    }
}
