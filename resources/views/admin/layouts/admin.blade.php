<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — SchoolShare</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --ss-primary:       #f59e0b; /* Admin uses amber primary */
            --ss-primary-dark:  #d97706;
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

        .ss-shell { display: flex; flex: 1; min-height: calc(100vh - 60px); }

        .ss-sidebar {
            width: var(--ss-sidebar-w);
            background: var(--ss-dark-2);
            border-right: 1px solid var(--ss-border);
            padding: 1.25rem 0;
            flex-shrink: 0;
            height: calc(100vh - 60px);
            position: sticky;
            top: 60px;
            overflow-y: auto;
        }
        .ss-sidebar-section {
            font-size: 0.7rem; font-weight: 600; text-transform: uppercase;
            letter-spacing: 0.08em; color: var(--ss-text-muted);
            padding: 0 1.25rem; margin: 1rem 0 0.35rem;
        }
        .ss-sidebar-link {
            display: flex; align-items: center; gap: 0.6rem; padding: 0.55rem 1.25rem;
            color: var(--ss-text-muted); text-decoration: none; font-size: 0.875rem;
            transition: background .15s, color .15s; border-left: 3px solid transparent;
        }
        .ss-sidebar-link:hover { background: var(--ss-dark-3); color: var(--ss-text); }
        .ss-sidebar-link.active {
            color: var(--ss-primary);
            background: rgba(245,158,11,.1);
            border-left-color: var(--ss-primary);
        }

        .ss-main { flex: 1; padding: 2rem; overflow-y: auto; }

        .ss-card {
            background: var(--ss-dark-2);
            border: 1px solid var(--ss-border);
            border-radius: 12px; padding: 1.5rem;
        }

        .btn-ss-primary { background: var(--ss-primary); color: #fff; border: none; border-radius: 8px; font-weight: 600; }
        .btn-ss-outline { background: transparent; color: var(--ss-text); border: 1px solid var(--ss-border); border-radius: 8px; font-weight: 500; }
        
        .alert-ss { border-radius: 10px; border: 1px solid; font-size: 0.875rem; }
        .alert-ss-success { background: rgba(16,185,129,.1); border-color: rgba(16,185,129,.3); color: #6ee7b7; }
    </style>
</head>
<body>

<nav class="ss-topbar d-flex align-items-center px-3 gap-3">
    <a href="{{ route('admin.dashboard') }}" class="ss-brand me-3">
        Admin<span>Panel</span>
    </a>
    <div class="ms-auto d-flex align-items-center gap-3">
        <a href="{{ route('dashboard') }}" class="btn btn-sm btn-ss-outline">Exit Admin</a>
        <span class="small" style="color:var(--ss-text-muted);">{{ auth()->user()->name }}</span>
    </div>
</nav>

<div class="ss-shell">
    <aside class="ss-sidebar">
        <div class="ss-sidebar-section">Overview</div>
        <a href="{{ route('admin.dashboard') }}" class="ss-sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        
        <div class="ss-sidebar-section">Management</div>
        <a href="{{ route('admin.users.index') }}" class="ss-sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
            <i class="bi bi-people"></i> Users
        </a>
        <a href="{{ route('admin.upgrades.index') }}" class="ss-sidebar-link {{ request()->routeIs('admin.upgrades.*') ? 'active' : '' }}">
            <i class="bi bi-arrow-up-circle"></i> Upgrade Requests
        </a>
    </aside>

    <main class="ss-main">
        @if (session('success'))
            <div class="alert alert-ss alert-ss-success d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
            </div>
        @endif
        @yield('content')
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
