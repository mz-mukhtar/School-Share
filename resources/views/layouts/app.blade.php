<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="SchoolShare — Your school work, always with you. Upload, version, and access your files anywhere.">
    <title>@yield('title', 'Dashboard') — SchoolShare by EthioNext</title>

    {{-- Bootstrap 5 CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --ss-primary:       #2563eb;
            --ss-primary-dark:  #1d4ed8;
            --ss-accent:        #06b6d4;
            --ss-success:       #10b981;
            --ss-warning:       #f59e0b;
            --ss-danger:        #ef4444;
            --ss-dark:          #0f172a;
            --ss-dark-2:        #1e293b;
            --ss-dark-3:        #334155;
            --ss-text:          #e2e8f0;
            --ss-text-muted:    #94a3b8;
            --ss-border:        #2d3748;
            --ss-sidebar-w:     260px;
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

        /* ── Top Navbar ─────────────────────────────────────────── */
        .ss-topbar {
            background: var(--ss-dark-2);
            border-bottom: 1px solid var(--ss-border);
            height: 60px;
            position: sticky;
            top: 0;
            z-index: 1030;
        }
        .ss-brand {
            font-weight: 800;
            font-size: 1.2rem;
            color: var(--ss-primary) !important;
            letter-spacing: -0.5px;
            text-decoration: none !important;
        }
        .ss-brand span { color: var(--ss-text); font-weight: 400; }
        .ss-nav-link {
            color: var(--ss-text-muted) !important;
            font-size: 0.875rem;
            transition: color .15s;
            padding: 0.4rem 0.75rem !important;
            border-radius: 6px;
        }
        .ss-nav-link:hover, .ss-nav-link.active {
            color: var(--ss-text) !important;
            background: var(--ss-dark-3);
        }
        .ss-avatar {
            width: 32px; height: 32px; border-radius: 50%;
            background: var(--ss-primary);
            display: inline-flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.75rem; color: #fff;
        }

        /* ── Layout shell ──────────────────────────────────────── */
        .ss-shell { display: flex; flex: 1; min-height: calc(100vh - 60px); }

        /* ── Sidebar ───────────────────────────────────────────── */
        .ss-sidebar {
            width: var(--ss-sidebar-w);
            background: var(--ss-dark-2);
            border-right: 1px solid var(--ss-border);
            padding: 1.25rem 0;
            flex-shrink: 0;
            position: sticky;
            top: 60px;
            height: calc(100vh - 60px);
            overflow-y: auto;
        }
        .ss-sidebar-section {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--ss-text-muted);
            padding: 0 1.25rem;
            margin: 1rem 0 0.35rem;
        }
        .ss-sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.55rem 1.25rem;
            color: var(--ss-text-muted);
            text-decoration: none;
            font-size: 0.875rem;
            transition: background .15s, color .15s;
            border-left: 3px solid transparent;
        }
        .ss-sidebar-link:hover {
            background: var(--ss-dark-3);
            color: var(--ss-text);
        }
        .ss-sidebar-link.active {
            color: var(--ss-primary);
            background: rgba(37,99,235,.1);
            border-left-color: var(--ss-primary);
        }
        .ss-sidebar-link i { font-size: 1rem; width: 18px; text-align: center; }

        /* Storage meter in sidebar */
        .ss-storage-meter { padding: 1rem 1.25rem; }
        .ss-storage-bar {
            height: 6px; border-radius: 3px;
            background: var(--ss-dark-3); overflow: hidden;
        }
        .ss-storage-fill {
            height: 100%; border-radius: 3px;
            background: linear-gradient(90deg, var(--ss-primary), var(--ss-accent));
            transition: width .4s ease;
        }

        /* ── Main content ──────────────────────────────────────── */
        .ss-main {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        /* ── Cards ─────────────────────────────────────────────── */
        .ss-card {
            background: var(--ss-dark-2);
            border: 1px solid var(--ss-border);
            border-radius: 12px;
            padding: 1.5rem;
        }

        /* ── Buttons ───────────────────────────────────────────── */
        .btn-ss-primary {
            background: var(--ss-primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background .15s, transform .1s;
        }
        .btn-ss-primary:hover {
            background: var(--ss-primary-dark);
            color: #fff;
            transform: translateY(-1px);
        }
        .btn-ss-outline {
            background: transparent;
            color: var(--ss-text);
            border: 1px solid var(--ss-border);
            border-radius: 8px;
            font-weight: 500;
            transition: background .15s;
        }
        .btn-ss-outline:hover {
            background: var(--ss-dark-3);
            color: var(--ss-text);
        }

        /* ── Footer ─────────────────────────────────────────────── */
        .ss-footer {
            background: var(--ss-dark-2);
            border-top: 1px solid var(--ss-border);
            padding: 0.75rem 1.5rem;
            font-size: 0.75rem;
            color: var(--ss-text-muted);
            text-align: center;
        }
        .ss-footer a { color: var(--ss-text-muted); text-decoration: none; }
        .ss-footer a:hover { color: var(--ss-primary); }

        /* ── Alerts ─────────────────────────────────────────────── */
        .alert-ss {
            border-radius: 10px;
            border: 1px solid;
            font-size: 0.875rem;
        }
        .alert-ss-success {
            background: rgba(16,185,129,.1);
            border-color: rgba(16,185,129,.3);
            color: #6ee7b7;
        }
        .alert-ss-danger {
            background: rgba(239,68,68,.1);
            border-color: rgba(239,68,68,.3);
            color: #fca5a5;
        }
        .alert-ss-warning {
            background: rgba(245,158,11,.1);
            border-color: rgba(245,158,11,.3);
            color: #fcd34d;
        }

        /* ── Responsive ─────────────────────────────────────────── */
        @media (max-width: 768px) {
            .ss-sidebar { display: none; }
            .ss-main { padding: 1rem; }
        }

        /* ── Scrollbar ──────────────────────────────────────────── */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--ss-dark); }
        ::-webkit-scrollbar-thumb { background: var(--ss-dark-3); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--ss-text-muted); }
    </style>

    @stack('styles')
</head>
<body>

{{-- ── Top Navbar ─────────────────────────────────────────── --}}
<nav class="ss-topbar d-flex align-items-center px-3 gap-3">
    <a href="{{ route('dashboard') }}" class="ss-brand me-3">
        School<span>Share</span>
    </a>

    {{-- Mobile hamburger --}}
    <button class="btn btn-sm d-md-none ms-auto me-2" style="color:var(--ss-text-muted);" data-bs-toggle="offcanvas" data-bs-target="#ssSidebar">
        <i class="bi bi-list fs-5"></i>
    </button>

    {{-- Search --}}
    <form class="d-none d-md-flex flex-grow-1" style="max-width:360px;" method="GET" action="{{ route('dashboard') }}">
        <div class="input-group input-group-sm">
            <span class="input-group-text" style="background:var(--ss-dark-3);border-color:var(--ss-border);color:var(--ss-text-muted);">
                <i class="bi bi-search"></i>
            </span>
            <input type="text" name="q" class="form-control form-control-sm"
                   placeholder="Search projects…"
                   style="background:var(--ss-dark-3);border-color:var(--ss-border);color:var(--ss-text);"
                   value="{{ request('q') }}">
        </div>
    </form>

    <div class="ms-auto d-flex align-items-center gap-2">
        {{-- New Project button --}}
        @auth
        <a href="{{ route('projects.create') }}" class="btn btn-ss-primary btn-sm d-none d-md-inline-flex align-items-center gap-1">
            <i class="bi bi-plus-lg"></i> New Project
        </a>
        @endauth

        {{-- User dropdown --}}
        @auth
        <div class="dropdown">
            <button class="btn d-flex align-items-center gap-2 p-0 border-0" type="button" data-bs-toggle="dropdown" style="background:none;">
                <span class="ss-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                <span class="d-none d-md-inline small" style="color:var(--ss-text-muted);">{{ auth()->user()->name }}</span>
                <i class="bi bi-chevron-down small" style="color:var(--ss-text-muted);"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" style="background:var(--ss-dark-2);border-color:var(--ss-border);">
                <li><a class="dropdown-item" href="{{ route('profile.edit') }}" style="color:var(--ss-text-muted);">
                    <i class="bi bi-person me-2"></i>Profile</a></li>
                <li><hr class="dropdown-divider" style="border-color:var(--ss-border);"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item" style="color:#fca5a5;">
                            <i class="bi bi-box-arrow-right me-2"></i>Log Out
                        </button>
                    </form>
                </li>
            </ul>
        </div>
        @endauth
    </div>
</nav>

{{-- ── Shell (sidebar + main) ─────────────────────────────── --}}
<div class="ss-shell">

    {{-- ── Sidebar (desktop) ──────────────────────────────── --}}
    @auth
    <aside class="ss-sidebar d-none d-md-block">
        <div class="ss-sidebar-section">Main</div>
        <a href="{{ route('dashboard') }}" class="ss-sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-grid-1x2"></i> Dashboard
        </a>
        <a href="{{ route('projects.index') }}" class="ss-sidebar-link {{ request()->routeIs('projects.*') ? 'active' : '' }}">
            <i class="bi bi-folder2-open"></i> My Projects
        </a>
        <a href="{{ route('explore') }}" class="ss-sidebar-link {{ request()->routeIs('explore') ? 'active' : '' }}">
            <i class="bi bi-compass"></i> Explore
        </a>

        <div class="ss-sidebar-section">Account</div>
        <a href="{{ route('profile.edit') }}" class="ss-sidebar-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
            <i class="bi bi-person"></i> Profile
        </a>

        {{-- Storage meter --}}
        <div class="ss-storage-meter mt-3">
            @php
                $user = auth()->user();
                $pct  = $user->storageUsagePercent();
                $fill = min(100, $pct);
                $color = $fill >= 90 ? '#ef4444' : ($fill >= 70 ? '#f59e0b' : null);
            @endphp
            <div class="d-flex justify-content-between mb-1">
                <span style="font-size:0.72rem;color:var(--ss-text-muted);">Storage</span>
                <span style="font-size:0.72rem;color:var(--ss-text-muted);">{{ $user->storageUsedHuman() }} / {{ $user->maxStorageHuman() }}</span>
            </div>
            <div class="ss-storage-bar">
                <div class="ss-storage-fill" style="width:{{ $fill }}%;{{ $color ? 'background:'.$color.';' : '' }}"></div>
            </div>
            <div class="mt-1" style="font-size:0.7rem;color:var(--ss-text-muted);">{{ $pct }}% used</div>
        </div>
    </aside>
    @endauth

    {{-- ── Offcanvas sidebar (mobile) ─────────────────────── --}}
    @auth
    <div class="offcanvas offcanvas-start" tabindex="-1" id="ssSidebar" style="background:var(--ss-dark-2);border-right:1px solid var(--ss-border);">
        <div class="offcanvas-header">
            <span class="ss-brand">School<span>Share</span></span>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0">
            <div class="ss-sidebar-section">Main</div>
            <a href="{{ route('dashboard') }}" class="ss-sidebar-link">
                <i class="bi bi-grid-1x2"></i> Dashboard
            </a>
            <a href="{{ route('projects.index') }}" class="ss-sidebar-link">
                <i class="bi bi-folder2-open"></i> My Projects
            </a>
            <a href="{{ route('explore') }}" class="ss-sidebar-link">
                <i class="bi bi-compass"></i> Explore
            </a>
            <div class="ss-sidebar-section">Account</div>
            <a href="{{ route('profile.edit') }}" class="ss-sidebar-link">
                <i class="bi bi-person"></i> Profile
            </a>
            <form method="POST" action="{{ route('logout') }}" class="p-3">
                @csrf
                <button type="submit" class="btn btn-ss-outline w-100">
                    <i class="bi bi-box-arrow-right me-1"></i> Log Out
                </button>
            </form>
        </div>
    </div>
    @endauth

    {{-- ── Main content area ───────────────────────────────── --}}
    <main class="ss-main">

        {{-- Session flash messages --}}
        @if (session('success'))
            <div class="alert alert-ss alert-ss-success d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-ss alert-ss-danger d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-exclamation-circle-fill"></i> {{ session('error') }}
            </div>
        @endif
        @if (session('warning'))
            <div class="alert alert-ss alert-ss-warning d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-exclamation-triangle-fill"></i> {{ session('warning') }}
            </div>
        @endif

        @yield('content')
    </main>
</div>

{{-- ── Footer ─────────────────────────────────────────────── --}}
<footer class="ss-footer">
    {{ config('schoolshare.branding.footer_text') }} &middot;
    <a href="https://schoolshare.ethionext.com.et">schoolshare.ethionext.com.et</a>
    &middot;
    <a href="{{ config('schoolshare.branding.github_url') }}" target="_blank">GitHub</a>
    &middot;
    <a href="mailto:{{ config('schoolshare.branding.contact_email') }}">Contact</a>
</footer>

{{-- Bootstrap 5 JS --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
