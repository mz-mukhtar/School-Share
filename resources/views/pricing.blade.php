@extends('layouts.app')

@section('title', 'Pricing')
@section('meta_description', 'Choose the right SchoolShare plan for you. Free open-source core, with premium white-label options.')

@section('content')
@push('styles')
<style>
    .ss-pricing-card {
        background: var(--ss-dark-2);
        border: 1px solid var(--ss-border);
        border-radius: 20px; padding: 2rem;
        height: 100%;
        transition: transform .2s, border-color .2s;
    }
    .ss-pricing-card.featured {
        border-color: var(--ss-primary);
        background: linear-gradient(160deg, rgba(37,99,235,.1) 0%, var(--ss-dark-2) 40%);
        box-shadow: 0 0 40px rgba(37,99,235,.2);
    }
    .ss-pricing-card:hover { transform: translateY(-4px); }
    .ss-pricing-badge {
        font-size: .7rem; font-weight: 700; text-transform: uppercase;
        padding: .22rem .7rem; border-radius: 999px;
        background: var(--ss-primary); color: #fff;
        margin-bottom: .85rem; display: inline-block;
    }
    .ss-price-amount { font-size: 2.3rem; font-weight: 900; letter-spacing: -.03em; line-height: 1; }
    .ss-price-period { font-size: .82rem; color: var(--ss-text-muted); margin-left: .2rem; }
    .ss-price-desc { font-size: .85rem; color: var(--ss-text-muted); margin: .45rem 0 1.35rem; }
    .ss-price-feature {
        display: flex; align-items: flex-start; gap: .55rem;
        font-size: .85rem; margin-bottom: .55rem; color: var(--ss-text-muted);
    }
    .ss-price-feature i { color: var(--ss-success); font-size: .9rem; flex-shrink: 0; margin-top: .07rem; }
    .btn-pricing-primary {
        background: var(--ss-primary); color: #fff; border: none;
        width: 100%; padding: .7rem; border-radius: 10px;
        font-weight: 700; font-size: .9rem;
        text-decoration: none; display: block; text-align: center;
        transition: background .15s, transform .1s;
        margin-top: auto;
    }
    .btn-pricing-primary:hover { background: var(--ss-primary-dark); color: #fff; transform: translateY(-1px); }
    .btn-pricing-outline {
        background: transparent; color: var(--ss-text);
        border: 1px solid var(--ss-border);
        width: 100%; padding: .7rem; border-radius: 10px;
        font-weight: 600; font-size: .9rem;
        text-decoration: none; display: block; text-align: center;
        transition: background .15s;
        margin-top: auto;
    }
    .btn-pricing-outline:hover { background: rgba(255,255,255,.05); color: var(--ss-text); }
    .ss-footer-col-title {
        font-size: .68rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .08em; color: var(--ss-text-muted); margin-bottom: .7rem;
    }
</style>
@endpush
<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-5 fw-bold text-white mb-3">Simple, Transparent <span style="color:var(--ss-primary);">Pricing</span></h1>
        <p class="lead text-muted">Whether you're an individual student or an entire school, we have a plan for you.</p>
    </div>

    <div class="row g-4 justify-content-center align-items-stretch mt-4">
        <div class="col-md-4">
            <div class="ss-pricing-card d-flex flex-column h-100 position-relative">
                @auth
                <div class="position-absolute top-0 end-0 p-3" style="z-index: 10;">
                    <span class="badge bg-secondary">Current</span>
                </div>
                @endauth
                <div>
                    <div class="ss-footer-col-title">Student</div>
                    <div class="ss-price-amount">Free<span class="ss-price-period">/ forever</span></div>
                    <div class="ss-price-desc">Everything you need for school. No credit card.</div>
                </div>
                <div class="flex-grow-1">
                    @foreach(['15 Projects','3 GB Storage','100 MB per file','Full version history','In-browser viewer & editor','Download as ZIP','Collaboration (up to 5)'] as $f)
                    <div class="ss-price-feature"><i class="bi bi-check-circle-fill"></i> {{ $f }}</div>
                    @endforeach
                </div>
                @auth
                    <button class="btn-pricing-outline mt-3 disabled w-100">Your Current Plan</button>
                @else
                    <a href="{{ route('register') }}" class="btn-pricing-outline mt-3 text-center">Get started free</a>
                @endauth
            </div>
        </div>
        <div class="col-md-4">
            <div class="ss-pricing-card featured d-flex flex-column h-100">
                <div>
                    <div class="ss-pricing-badge">Most Popular</div>
                    <div class="ss-footer-col-title">Pro</div>
                    <div class="ss-price-amount">Contact<span class="ss-price-period"> us</span></div>
                    <div class="ss-price-desc">For power users who need more storage and projects.</div>
                </div>
                <div class="flex-grow-1">
                    @foreach(['Everything in Student','20 Projects','10 GB Storage','Priority support','Early access to new features'] as $f)
                    <div class="ss-price-feature"><i class="bi bi-check-circle-fill"></i> {{ $f }}</div>
                    @endforeach
                </div>
                <a href="mailto:mahizeki037@gmail.com?subject=SchoolShare Pro Inquiry" class="btn-pricing-primary mt-3 text-center">Contact to upgrade</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="ss-pricing-card d-flex flex-column h-100">
                <div>
                    <div class="ss-footer-col-title">White-label</div>
                    <div class="ss-price-amount">Custom</div>
                    <div class="ss-price-desc">Run SchoolShare under your school's brand. No EthioNext branding.</div>
                </div>
                <div class="flex-grow-1">
                    @foreach(['Everything in Pro','Custom branding & logo','Self-hosted or managed','School-wide deployment','Commercial license key','Developer support'] as $f)
                    <div class="ss-price-feature"><i class="bi bi-check-circle-fill"></i> {{ $f }}</div>
                    @endforeach
                </div>
                <a href="mailto:mahizeki037@gmail.com?subject=SchoolShare White-label License" class="btn-pricing-outline mt-3 text-center">Get a license</a>
            </div>
        </div>
    </div>
</div>
@endsection
