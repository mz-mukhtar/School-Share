@extends('layouts.guest')
@section('title', 'Verify Your Email')

@push('styles')
<style>
    .otp-inputs {
        display: flex;
        gap: 0.75rem;
        justify-content: center;
        margin: 1.5rem 0;
    }
    .otp-digit {
        width: 52px;
        height: 60px;
        text-align: center;
        font-size: 1.5rem;
        font-weight: 700;
        background: var(--ss-dark-3) !important;
        border: 2px solid var(--ss-border) !important;
        border-radius: 12px !important;
        color: var(--ss-text) !important;
        transition: border-color .2s, box-shadow .2s;
        caret-color: var(--ss-primary);
    }
    .otp-digit:focus {
        border-color: var(--ss-primary) !important;
        box-shadow: 0 0 0 3px rgba(37,99,235,.25) !important;
        outline: none;
    }
    .otp-digit.is-invalid {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 3px rgba(239,68,68,.2) !important;
    }
    .otp-icon {
        width: 64px; height: 64px;
        background: linear-gradient(135deg, rgba(37,99,235,.2), rgba(6,182,212,.2));
        border-radius: 16px;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 1.25rem;
        font-size: 1.75rem;
    }
    .resend-timer { font-size: .8rem; color: var(--ss-text-muted); margin-top: .5rem; }
</style>
@endpush

@section('content')
    {{-- Icon --}}
    <div class="otp-icon">📧</div>

    <h1 class="text-center">Check your email</h1>
    <p class="subtitle text-center">
        We sent a 6-digit verification code to<br>
        <strong style="color:var(--ss-text);">{{ $email }}</strong>
    </p>

    {{-- Success message --}}
    @if (session('success'))
        <div class="alert" style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);color:#6ee7b7;border-radius:10px;font-size:.85rem;padding:.75rem 1rem;margin-bottom:1rem;">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        </div>
    @endif

    {{-- Error message --}}
    @if ($errors->any())
        <div class="alert" style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#fca5a5;border-radius:10px;font-size:.85rem;padding:.75rem 1rem;margin-bottom:1rem;">
            <i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first() }}
        </div>
    @endif

    {{-- OTP form --}}
    <form method="POST" action="{{ route('otp.verify') }}" id="otpForm">
        @csrf

        {{-- Hidden input that gets populated by the digit inputs --}}
        <input type="hidden" name="otp" id="otpHidden">

        {{-- 6 visual digit boxes --}}
        <div class="otp-inputs" id="otpBoxes">
            @for ($i = 0; $i < 6; $i++)
                <input
                    type="text"
                    inputmode="numeric"
                    maxlength="1"
                    class="otp-digit {{ $errors->has('otp') ? 'is-invalid' : '' }}"
                    id="otp-{{ $i }}"
                    autocomplete="off"
                    aria-label="Digit {{ $i + 1 }} of 6"
                >
            @endfor
        </div>

        <button type="submit" class="btn btn-ss-primary" id="verifyBtn" disabled>
            <i class="bi bi-shield-check me-1"></i> Verify Email
        </button>
    </form>

    {{-- Resend --}}
    <div class="text-center mt-4">
        <p class="resend-timer" id="resendHint">
            Didn't receive it? <span id="countdown"></span>
        </p>
        <form method="POST" action="{{ route('otp.resend') }}" id="resendForm" style="display:inline;">
            @csrf
            <button type="submit" class="ss-link bg-transparent border-0 p-0" id="resendBtn" style="display:none;">
                Resend code
            </button>
        </form>
    </div>

    <hr style="border-color:var(--ss-border);margin:1.5rem 0;">
    <p class="text-center mb-0" style="font-size:.8rem;color:var(--ss-text-muted);">
        Wrong email? <a href="{{ route('logout') }}" onclick="event.preventDefault();document.getElementById('logoutForm').submit();" class="ss-link">Log out</a> and register again.
    </p>
    <form id="logoutForm" method="POST" action="{{ route('logout') }}" class="d-none">@csrf</form>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script>
(function () {
    const digits   = Array.from(document.querySelectorAll('.otp-digit'));
    const hidden   = document.getElementById('otpHidden');
    const verifyBtn = document.getElementById('verifyBtn');
    const resendBtn = document.getElementById('resendBtn');
    const countdownEl = document.getElementById('countdown');
    const resendHint = document.getElementById('resendHint');

    // ── Auto-advance on input ──────────────────────────────────────
    digits.forEach((el, i) => {
        el.addEventListener('input', e => {
            // Only allow digits
            el.value = el.value.replace(/\D/g, '').slice(-1);
            if (el.value && i < digits.length - 1) digits[i + 1].focus();
            syncHidden();
        });

        el.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && !el.value && i > 0) {
                digits[i - 1].focus();
                digits[i - 1].value = '';
                syncHidden();
            }
        });

        // Handle paste on any digit
        el.addEventListener('paste', e => {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
            pasted.split('').slice(0, 6).forEach((ch, idx) => {
                if (digits[idx]) digits[idx].value = ch;
            });
            const next = Math.min(pasted.length, 5);
            digits[next].focus();
            syncHidden();
        });
    });

    function syncHidden() {
        const val = digits.map(d => d.value).join('');
        hidden.value = val;
        verifyBtn.disabled = val.length < 6;
    }

    // Focus first digit on load
    digits[0].focus();

    // ── 60-second resend countdown ─────────────────────────────────
    let seconds = 60;
    function tick() {
        if (seconds > 0) {
            countdownEl.textContent = '(' + seconds + 's)';
            seconds--;
            setTimeout(tick, 1000);
        } else {
            resendHint.innerHTML = "Didn't receive it?";
            resendBtn.style.display = 'inline';
        }
    }
    tick();
})();
</script>
@endpush
