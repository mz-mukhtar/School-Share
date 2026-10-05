<?php

namespace App;

use App\Mail\OtpVerificationMail;
use App\Models\EmailOtpToken;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailOtpService
{
    public const WINDOW_MINUTES = 10;

    public const MAX_SENDS = 3;

    public const MAX_FAILED_ATTEMPTS = 5;

    public const CODE_TTL_MINUTES = 15;

    public function send(User $user): bool
    {
        $delivery = DB::transaction(function () use ($user): ?array {
            $recipient = User::query()->lockForUpdate()->findOrFail($user->id);
            if ($recipient->hasVerifiedEmail()) {
                EmailOtpToken::where('user_id', $recipient->id)->delete();

                return null;
            }

            $token = EmailOtpToken::where('user_id', $recipient->id)->first();
            $windowStart = $token?->send_window_started_at;
            $sendCount = $token?->send_count ?? 0;
            if (! $windowStart || $windowStart->lessThanOrEqualTo(now()->subMinutes(self::WINDOW_MINUTES))) {
                $windowStart = now();
                $sendCount = 0;
            }
            abort_if($sendCount >= self::MAX_SENDS, 429, 'Too many code requests. Please try again after ten minutes.', [
                'Retry-After' => (string) max(1, (int) ceil(now()->diffInSeconds($windowStart->copy()->addMinutes(self::WINDOW_MINUTES)))),
            ]);

            do {
                $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $digest = $this->digest($recipient->id, $recipient->email, $code);
            } while ($token && hash_equals($token->otp_hash, $digest));

            $token = EmailOtpToken::updateOrCreate(['user_id' => $recipient->id], [
                'email' => $recipient->email,
                'otp_hash' => $digest,
                'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
                'failed_attempts' => 0,
                'send_count' => $sendCount + 1,
                'send_window_started_at' => $windowStart,
                'delivery_status' => 'pending',
            ]);

            return [$recipient, $token, $code];
        });

        if ($delivery === null) {
            return true;
        }
        [$recipient, $token, $code] = $delivery;

        try {
            Mail::to($recipient->email)->send(new OtpVerificationMail($code, $recipient->name));
            $status = 'sent';
        } catch (Throwable $exception) {
            // Transport messages may contain credentials or message contents.
            report(new \RuntimeException('OTP mail delivery failed ('.$exception::class.').'));
            $status = 'failed';
        }

        EmailOtpToken::whereKey($token->id)->where('otp_hash', $token->otp_hash)->update(['delivery_status' => $status]);

        return $status === 'sent';
    }

    public function verify(User $user, string $code): ?string
    {
        $verified = false;
        $error = DB::transaction(function () use ($user, $code, &$verified): ?string {
            $account = User::query()->lockForUpdate()->findOrFail($user->id);
            if ($account->hasVerifiedEmail()) {
                EmailOtpToken::where('user_id', $account->id)->delete();

                return null;
            }

            $token = EmailOtpToken::where('user_id', $account->id)->first();
            if (! $token || $token->email !== $account->email) {
                return 'The verification code is incorrect. Please request a new code.';
            }
            if ($token->isExpired()) {
                return 'This code has expired. Please request a new one.';
            }
            if ($token->failed_attempts >= self::MAX_FAILED_ATTEMPTS) {
                return 'Too many incorrect codes. Please request a new code.';
            }
            if (! hash_equals($token->otp_hash, $this->digest($account->id, $account->email, $code))) {
                $token->increment('failed_attempts');

                return 'The verification code is incorrect.';
            }

            $account->markEmailAsVerified();
            EmailOtpToken::where('user_id', $account->id)->delete();
            $user->refresh();
            $verified = true;

            return null;
        });

        if ($verified) {
            event(new Verified($user));
        }

        return $error;
    }

    private function digest(int $userId, string $email, string $code): string
    {
        $key = (string) config('app.key');
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7), true) ?: '';
        }
        throw_if($key === '', \LogicException::class, 'An application key is required for OTP verification.');

        return hash_hmac('sha256', json_encode([$userId, $email, $code], JSON_THROW_ON_ERROR), $key);
    }
}
