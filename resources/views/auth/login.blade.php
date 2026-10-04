@extends('layouts.guest')
@section('title', 'Log In')

@section('content')
    <h1>Welcome back</h1>
    <p class="subtitle">Log in to access your projects and files.</p>

    {{-- Session Status --}}
    @if (session('status'))
        <div class="alert alert-success mb-3 py-2 px-3" style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);color:#6ee7b7;border-radius:8px;font-size:.875rem;">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        {{-- Email --}}
        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}" required autofocus autocomplete="username"
                   placeholder="you@school.com">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Password --}}
        <div class="mb-3">
            <div class="d-flex justify-content-between mb-1">
                <label for="password" class="form-label mb-0">Password</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="ss-link" style="font-size:.8rem;">Forgot password?</a>
                @endif
            </div>
            <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                   required autocomplete="current-password" placeholder="••••••••">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Remember me --}}
        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="remember_me" name="remember"
                   style="background:var(--ss-dark-3);border-color:var(--ss-border);">
            <label class="form-check-label" for="remember_me" style="color:var(--ss-text-muted);font-size:.875rem;">
                Remember me
            </label>
        </div>

        <button type="submit" class="btn btn-ss-primary">Log in</button>

        <p class="text-center mt-3 mb-0" style="font-size:.875rem;color:var(--ss-text-muted);">
            New to SchoolShare? <a href="{{ route('register') }}" class="ss-link">Create an account</a>
        </p>
    </form>
@endsection
