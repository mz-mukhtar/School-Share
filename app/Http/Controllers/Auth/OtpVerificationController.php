<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpVerificationMail;
use App\Models\EmailOtpToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
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
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'digits:6'],
        ]);

        $user = $request->user();

        $token = EmailOtpToken::where('user_id', $user->id)
            ->where('otp', $request->otp)
            ->first();

        if (! $token) {
            return back()->withErrors(['otp' => 'The verification code is incorrect.']);
        }

        if ($token->isExpired()) {
            $token->delete();
            return back()->withErrors(['otp' => 'This code has expired. Please request a new one.']);
        }

        // Mark email as verified
        $user->email_verified_at = now();
        $user->save();

        // Delete all OTP tokens for this user
        EmailOtpToken::where('user_id', $user->id)->delete();

        return redirect()->route('dashboard')
            ->with('success', '🎉 Email verified! Welcome to SchoolShare.');
    }

    /**
     * Resend a fresh OTP to the user's email.
     */
    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->email_verified_at) {
            return redirect()->route('dashboard');
        }

        // Rate limit: max 3 resends — check how many tokens exist in last 10 minutes
        $recentCount = EmailOtpToken::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->count();

        if ($recentCount >= 3) {
            return back()->withErrors(['otp' => 'Too many resend requests. Please wait a few minutes before trying again.']);
        }

        // Delete old tokens and send a fresh one
        EmailOtpToken::where('user_id', $user->id)->delete();
        $this->sendOtp($user);

        return back()->with('success', 'A new verification code has been sent to your email.');
    }

    /**
     * Generate a random 6-digit OTP, store it, and email it.
     */
    public static function sendOtp(\App\Models\User $user): void
    {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        EmailOtpToken::create([
            'user_id'    => $user->id,
            'otp'        => $otp,
            'expires_at' => now()->addMinutes(15),
        ]);

        Mail::to($user->email)->send(new OtpVerificationMail($otp, $user->name));
    }
}
