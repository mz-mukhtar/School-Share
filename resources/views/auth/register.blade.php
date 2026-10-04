@extends('layouts.guest')
@section('title', 'Create Account')

@section('content')
    <h1>Create your account</h1>
    <p class="subtitle">Start tracking your school work for free — no credit card needed.</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        {{-- Name --}}
        <div class="mb-3">
            <label for="name" class="form-label">Full Name</label>
            <input id="name" type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name') }}" required autofocus autocomplete="name"
                   placeholder="e.g. Mahi Zeki Mukhtar">
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Email --}}
        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}" required autocomplete="username"
                   placeholder="you@school.com">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Password --}}
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                   required autocomplete="new-password" placeholder="Min. 8 characters">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Confirm Password --}}
        <div class="mb-4">
            <label for="password_confirmation" class="form-label">Confirm Password</label>
            <input id="password_confirmation" type="password" name="password_confirmation"
                   class="form-control @error('password_confirmation') is-invalid @enderror"
                   required autocomplete="new-password" placeholder="Repeat password">
            @error('password_confirmation')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-ss-primary">Create Account</button>

        <p class="text-center mt-3 mb-0" style="font-size:.875rem;color:var(--ss-text-muted);">
            Already have an account? <a href="{{ route('login') }}" class="ss-link">Log in</a>
        </p>

        <p class="text-center mt-2 mb-0" style="font-size:.72rem;color:var(--ss-text-muted);">
            By registering you agree to our <a href="#" class="ss-link">Terms of Service</a>.
        </p>
    </form>
@endsection
