<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmailOtpToken;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $verified = DB::transaction(function () use ($request): bool {
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->id);
            abort_unless(hash_equals(sha1($user->email), (string) $request->route('hash')), 403);
            $wasVerified = $user->hasVerifiedEmail();
            if (! $wasVerified) {
                $user->markEmailAsVerified();
            }
            EmailOtpToken::where('user_id', $user->id)->delete();
            $request->user()->refresh();

            return ! $wasVerified;
        });

        if ($verified) {
            event(new Verified($request->user()));
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
