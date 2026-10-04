<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="SchoolShare — Your school work, always with you.">
    <title>@yield('title', 'Welcome') — SchoolShare</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --ss-primary:     #2563eb;
            --ss-dark:        #0f172a;
            --ss-dark-2:      #1e293b;
            --ss-dark-3:      #334155;
            --ss-text:        #e2e8f0;
            --ss-text-muted:  #94a3b8;
            --ss-border:      #2d3748;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--ss-dark);
            color: var(--ss-text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .ss-guest-topbar {
            background: var(--ss-dark-2);
            border-bottom: 1px solid var(--ss-border);
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .ss-brand {
            font-weight: 800;
            font-size: 1.2rem;
            color: var(--ss-primary) !important;
            text-decoration: none !important;
        }
        .ss-brand span { color: var(--ss-text); font-weight: 400; }
        .ss-guest-body {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .ss-auth-card {
            background: var(--ss-dark-2);
            border: 1px solid var(--ss-border);
            border-radius: 16px;
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 420px;
        }
        .ss-auth-card h1 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        .ss-auth-card .subtitle {
            color: var(--ss-text-muted);
            font-size: 0.875rem;
            margin-bottom: 1.75rem;
        }
        /* Form overrides */
        .form-label {
            color: var(--ss-text-muted);
            font-size: 0.82rem;
            font-weight: 500;
            margin-bottom: 0.3rem;
        }
        .form-control {
            background: var(--ss-dark-3) !important;
            border: 1px solid var(--ss-border) !important;
            color: var(--ss-text) !important;
            border-radius: 8px;
        }
        .form-control:focus {
            border-color: var(--ss-primary) !important;
            box-shadow: 0 0 0 3px rgba(37,99,235,.2) !important;
        }
        .form-control::placeholder { color: var(--ss-text-muted) !important; }
        .btn-ss-primary {
            background: var(--ss-primary);
            color: #fff; border: none;
            border-radius: 8px; font-weight: 600;
            padding: 0.6rem 1rem;
            width: 100%;
            transition: background .15s, transform .1s;
        }
        .btn-ss-primary:hover {
            background: #1d4ed8; color: #fff;
            transform: translateY(-1px);
        }
        .ss-link { color: var(--ss-primary); text-decoration: none; font-size: 0.875rem; }
        .ss-link:hover { text-decoration: underline; }
        .ss-footer {
            background: var(--ss-dark-2);
            border-top: 1px solid var(--ss-border);
            padding: 0.75rem;
            text-align: center;
            font-size: 0.72rem;
            color: var(--ss-text-muted);
        }
        .ss-footer a { color: var(--ss-text-muted); text-decoration: none; }
        .ss-footer a:hover { color: var(--ss-primary); }
        .invalid-feedback { font-size: 0.78rem; }
    </style>

    @stack('styles')
</head>
<body>

<header class="ss-guest-topbar">
    <a href="{{ url('/') }}" class="ss-brand">School<span>Share</span></a>
    <div class="d-flex gap-2">
        @if (Route::has('login') && !request()->routeIs('login'))
        <a href="{{ route('login') }}" class="btn btn-sm btn-outline-secondary" style="border-color:var(--ss-border);color:var(--ss-text-muted);">Log in</a>
        @endif
        @if (Route::has('register') && !request()->routeIs('register'))
        <a href="{{ route('register') }}" class="btn btn-sm" style="background:var(--ss-primary);color:#fff;">Sign up</a>
        @endif
    </div>
</header>

<div class="ss-guest-body">
    <div class="ss-auth-card">
        @yield('content')
    </div>
</div>

<footer class="ss-footer">
    {{ config('schoolshare.branding.footer_text') }} &middot;
    <a href="https://schoolshare.ethionext.com.et">schoolshare.ethionext.com.et</a>
    &middot;
    <a href="{{ config('schoolshare.branding.github_url') }}" target="_blank">Open Source</a>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
