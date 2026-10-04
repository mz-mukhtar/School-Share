@extends('layouts.app')
@section('title', 'Upgrade Your Account')

@section('content')
<div style="max-width:640px;margin:0 auto;">

    <a href="{{ route('pricing') }}" class="d-inline-flex align-items-center gap-2 mb-4" style="color:var(--ss-text-muted);text-decoration:none;font-size:0.875rem;">
        <i class="bi bi-arrow-left"></i> Back to Pricing
    </a>

    <div class="ss-card">
        <div class="text-center mb-4">
            @if($plan === 'custom')
                <div style="font-size:2.5rem;">📞</div>
                <h2 class="mt-2 mb-1" style="font-weight:700;">Custom / White-label Plan</h2>
                <p style="color:var(--ss-text-muted);">Let's discuss your specific needs</p>
            @else
                <div style="font-size:2.5rem;">⚡</div>
                <h2 class="mt-2 mb-1" style="font-weight:700;">Upgrade to Pro</h2>
                <p style="color:var(--ss-text-muted);">Get unlimited storage and all Pro features</p>
            @endif
        </div>

        @if($existing)
            <div class="alert-ss alert-ss-warning p-3 rounded mb-4 d-flex align-items-start gap-2">
                <i class="bi bi-clock-history mt-1"></i>
                <div>
                    <strong>Pending Request</strong><br>
                    <span style="font-size:0.875rem;">You already have a pending upgrade request submitted {{ $existing->created_at->diffForHumans() }}. Check your email for payment instructions.</span>
                </div>
            </div>
        @endif

        @if($plan === 'custom')
            {{-- Custom plan contact info --}}
            <div class="ss-card mb-4" style="background:var(--ss-dark-3);">
                <p class="mb-3" style="color:var(--ss-text-muted);font-size:0.9rem;">For the Custom / White-label plan, please contact us directly:</p>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div style="width:44px;height:44px;background:var(--ss-primary);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">📱</div>
                    <div>
                        <div style="font-size:0.75rem;color:var(--ss-text-muted);">WhatsApp / Call</div>
                        <div style="font-size:1.25rem;font-weight:700;">0992194042</div>
                    </div>
                </div>
                <p style="color:var(--ss-text-muted);font-size:0.875rem;margin:0;">
                    Click "Request Info" below and we'll also send you our contact details by email.
                </p>
            </div>

            <form action="{{ route('upgrade.store') }}" method="POST">
                @csrf
                <input type="hidden" name="plan" value="custom">
                <input type="hidden" name="billing_cycle" value="monthly">
                <button type="submit" class="btn btn-ss-primary w-100 py-3" {{ $existing ? 'disabled' : '' }}>
                    <i class="bi bi-envelope me-2"></i>
                    {{ $existing ? 'Request Already Sent' : 'Send Me Contact Details' }}
                </button>
            </form>

        @else
            {{-- Pro plan billing cycle selector + form --}}
            <form action="{{ route('upgrade.store') }}" method="POST" id="upgradeForm">
                @csrf
                <input type="hidden" name="plan" value="pro">
                <input type="hidden" name="billing_cycle" id="billing_cycle_input" value="monthly">

                <p class="mb-2" style="font-weight:600;">Select your billing cycle:</p>

                <div class="d-flex gap-3 mb-4">
                    {{-- Monthly --}}
                    <label class="flex-fill" style="cursor:pointer;">
                        <input type="radio" name="_billing_ui" value="monthly" checked class="d-none billing-radio" data-value="monthly">
                        <div class="billing-card ss-card p-3 text-center" style="border:2px solid var(--ss-primary);border-radius:12px;transition:all .2s;">
                            <div style="font-size:1.5rem;font-weight:800;">200 <span style="font-size:0.9rem;font-weight:500;">ETB</span></div>
                            <div style="color:var(--ss-text-muted);font-size:0.8rem;">/ month</div>
                        </div>
                    </label>

                    {{-- Yearly --}}
                    <label class="flex-fill position-relative" style="cursor:pointer;">
                        <input type="radio" name="_billing_ui" value="yearly" class="d-none billing-radio" data-value="yearly">
                        <span class="position-absolute top-0 start-50 translate-middle badge" style="background:var(--ss-success);font-size:0.7rem;z-index:1;">Save 400 ETB</span>
                        <div class="billing-card ss-card p-3 text-center" style="border:2px solid var(--ss-dark-3);border-radius:12px;transition:all .2s;">
                            <div style="font-size:1.5rem;font-weight:800;">2,000 <span style="font-size:0.9rem;font-weight:500;">ETB</span></div>
                            <div style="color:var(--ss-text-muted);font-size:0.8rem;">/ year</div>
                        </div>
                    </label>
                </div>

                {{-- What happens info --}}
                <div class="alert-ss p-3 rounded mb-4" style="background:rgba(37,99,235,0.1);border:1px solid rgba(37,99,235,0.3);">
                    <p style="margin:0 0 8px;font-size:0.875rem;font-weight:600;color:#93c5fd;">📋 How this works:</p>
                    <ol style="margin:0;padding-left:1.25rem;color:var(--ss-text-muted);font-size:0.85rem;line-height:1.8;">
                        <li>Click the button below — we'll email you the payment details.</li>
                        <li>Send the payment via <strong style="color:var(--ss-text);">Telebirr (0992194042)</strong> or <strong style="color:var(--ss-text);">CBE (1000305157566)</strong>.</li>
                        <li>Take a screenshot of your receipt and email it to <strong style="color:var(--ss-text);">payment@ethionext.com.et</strong>.</li>
                        <li>We'll upgrade your account within <strong style="color:var(--ss-text);">24–48 hours</strong>. ✅</li>
                    </ol>
                </div>

                <button type="submit" class="btn btn-ss-primary w-100 py-3" id="submitBtn" {{ $existing ? 'disabled' : '' }}>
                    <i class="bi bi-envelope me-2"></i>
                    {{ $existing ? 'Instructions Already Sent — Check Your Email' : 'Send Me Payment Instructions' }}
                </button>
            </form>

            @push('scripts')
            <script>
            document.querySelectorAll('.billing-radio').forEach(radio => {
                radio.addEventListener('change', function() {
                    // Update hidden input
                    document.getElementById('billing_cycle_input').value = this.dataset.value;

                    // Update card borders
                    document.querySelectorAll('.billing-card').forEach(c => {
                        c.style.borderColor = 'var(--ss-dark-3)';
                    });
                    this.closest('label').querySelector('.billing-card').style.borderColor = 'var(--ss-primary)';
                });
            });
            </script>
            @endpush
        @endif
    </div>
</div>
@endsection
