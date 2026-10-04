<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="SchoolShare — Version control for students. Upload your school work, track changes, and access files from anywhere. No terminal required.">
    <meta property="og:title" content="SchoolShare — Version Control for Students">
    <meta property="og:description" content="Upload your school work, track every change, and access files from home, school, or anywhere. No terminal required.">
    <meta property="og:url" content="https://schoolshare.ethionext.com.et">
    <meta name="canonical" content="https://schoolshare.ethionext.com.et">
    <title>SchoolShare — Version Control for Students · by EthioNext</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400&display=swap" rel="stylesheet">

    <style>
        :root {
            --ss-primary:    #2563eb;
            --ss-primary-d:  #1d4ed8;
            --ss-accent:     #06b6d4;
            --ss-green:      #10b981;
            --ss-yellow:     #f59e0b;
            --ss-red:        #ef4444;
            --ss-dark:       #0a0f1e;
            --ss-dark-2:     #111827;
            --ss-dark-3:     #1e293b;
            --ss-dark-4:     #334155;
            --ss-text:       #f1f5f9;
            --ss-muted:      #94a3b8;
            --ss-border:     rgba(255,255,255,0.08);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--ss-dark);
            color: var(--ss-text);
            overflow-x: hidden;
        }

        /* ── Scrollbar ─────────────────────────────────────────── */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--ss-dark); }
        ::-webkit-scrollbar-thumb { background: var(--ss-dark-4); border-radius: 3px; }

        /* ── Navbar ─────────────────────────────────────────────── */
        .ss-nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
            padding: 1rem 2rem;
            display: flex; align-items: center; gap: 2rem;
            transition: background .3s, backdrop-filter .3s, border .3s;
        }
        .ss-nav.scrolled {
            background: rgba(10,15,30,0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--ss-border);
        }
        .ss-brand {
            font-weight: 900; font-size: 1.3rem;
            color: #fff; text-decoration: none;
            letter-spacing: -0.5px;
        }
        .ss-brand .accent { color: var(--ss-primary); }
        .ss-nav-links { display: flex; gap: 0.25rem; margin-left: auto; align-items: center; }
        .ss-nav-links a {
            color: var(--ss-muted); text-decoration: none;
            font-size: 0.875rem; font-weight: 500;
            padding: 0.4rem 0.85rem; border-radius: 8px;
            transition: color .15s, background .15s;
        }
        .ss-nav-links a:hover { color: var(--ss-text); background: rgba(255,255,255,.06); }
        .btn-nav-cta {
            background: var(--ss-primary); color: #fff !important;
            border-radius: 8px; font-weight: 600 !important;
            padding: 0.45rem 1.1rem !important;
            transition: background .15s, transform .1s !important;
        }
        .btn-nav-cta:hover { background: var(--ss-primary-d) !important; transform: translateY(-1px); }

        /* ── Hero ─────────────────────────────────────────────── */
        .ss-hero {
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            text-align: center;
            padding: 8rem 1.5rem 5rem;
            position: relative;
            overflow: hidden;
        }
        .ss-hero-bg {
            position: absolute; inset: 0; z-index: 0;
            background: radial-gradient(ellipse 80% 60% at 50% -10%, rgba(37,99,235,.35) 0%, transparent 70%),
                        radial-gradient(ellipse 50% 40% at 80% 60%, rgba(6,182,212,.15) 0%, transparent 70%),
                        radial-gradient(ellipse 40% 30% at 20% 80%, rgba(16,185,129,.1) 0%, transparent 70%);
        }
        /* Subtle grid overlay */
        .ss-hero-bg::after {
            content: '';
            position: absolute; inset: 0;
            background-image: linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
            background-size: 60px 60px;
        }
        .ss-hero-content { position: relative; z-index: 1; max-width: 820px; }
        .ss-badge {
            display: inline-flex; align-items: center; gap: 0.5rem;
            background: rgba(37,99,235,.15); border: 1px solid rgba(37,99,235,.3);
            color: #93c5fd; font-size: 0.8rem; font-weight: 600;
            padding: 0.35rem 1rem; border-radius: 999px; margin-bottom: 1.5rem;
        }
        .ss-badge .dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: var(--ss-accent); animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: .5; transform: scale(1.4); }
        }
        .ss-hero h1 {
            font-size: clamp(2.4rem, 6vw, 4.2rem);
            font-weight: 900; line-height: 1.1;
            letter-spacing: -0.03em;
            margin-bottom: 1.25rem;
        }
        .ss-hero h1 .highlight {
            background: linear-gradient(135deg, var(--ss-primary) 0%, var(--ss-accent) 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .ss-hero p.lead {
            font-size: clamp(1rem, 2.5vw, 1.2rem);
            color: var(--ss-muted); max-width: 560px;
            margin: 0 auto 2.5rem; line-height: 1.7; font-weight: 400;
        }
        .ss-hero-ctas { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
        .btn-hero-primary {
            background: var(--ss-primary);
            color: #fff; border: none;
            padding: 0.85rem 2rem; border-radius: 10px;
            font-size: 1rem; font-weight: 700;
            text-decoration: none; display: inline-flex; align-items: center; gap: .5rem;
            transition: background .2s, transform .15s, box-shadow .2s;
            box-shadow: 0 0 30px rgba(37,99,235,.4);
        }
        .btn-hero-primary:hover {
            background: var(--ss-primary-d); color: #fff;
            transform: translateY(-2px); box-shadow: 0 0 50px rgba(37,99,235,.5);
        }
        .btn-hero-secondary {
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.15);
            color: var(--ss-text);
            padding: 0.85rem 2rem; border-radius: 10px;
            font-size: 1rem; font-weight: 600;
            text-decoration: none; display: inline-flex; align-items: center; gap: .5rem;
            transition: background .2s, transform .15s;
            backdrop-filter: blur(8px);
        }
        .btn-hero-secondary:hover {
            background: rgba(255,255,255,.1); color: var(--ss-text);
            transform: translateY(-2px);
        }
        /* Hero app mockup */
        .ss-hero-mockup {
            margin-top: 4rem;
            position: relative;
            display: inline-block;
        }
        .ss-mockup-window {
            background: var(--ss-dark-3);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 40px 100px rgba(0,0,0,.6), 0 0 0 1px rgba(255,255,255,.05);
            max-width: 760px; width: 100%;
        }
        .ss-mockup-bar {
            background: var(--ss-dark-2);
            padding: 0.75rem 1rem;
            display: flex; align-items: center; gap: 0.5rem;
            border-bottom: 1px solid rgba(255,255,255,.07);
        }
        .ss-mockup-dot { width: 12px; height: 12px; border-radius: 50%; }
        .ss-mockup-body { padding: 1.25rem; }
        .ss-mockup-row {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.55rem 0.75rem; border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: background .15s;
        }
        .ss-mockup-row:hover { background: rgba(255,255,255,.04); }
        .ss-mockup-icon {
            width: 34px; height: 34px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; flex-shrink: 0;
        }
        .ss-mockup-filename { font-size: .85rem; font-weight: 600; }
        .ss-mockup-meta { font-size: .72rem; color: var(--ss-muted); margin-top: .1rem; }
        .ss-checkpoint-badge {
            font-size: .7rem; padding: .2rem .55rem; border-radius: 999px;
            font-weight: 600; margin-left: auto; flex-shrink: 0;
        }

        /* ── Shared section styles ───────────────────────────── */
        .ss-section { padding: 6rem 1.5rem; }
        .ss-section-label {
            font-size: .75rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .1em;
            color: var(--ss-primary); margin-bottom: .75rem;
        }
        .ss-section-title {
            font-size: clamp(1.8rem, 4vw, 2.8rem);
            font-weight: 800; letter-spacing: -.03em; line-height: 1.15;
            margin-bottom: 1rem;
        }
        .ss-section-sub {
            font-size: 1.05rem; color: var(--ss-muted);
            max-width: 540px; margin: 0 auto 3.5rem; line-height: 1.7;
        }

        /* ── How It Works ────────────────────────────────────── */
        .ss-how { background: var(--ss-dark-2); }
        .ss-step-num {
            width: 52px; height: 52px; border-radius: 50%;
            background: linear-gradient(135deg, var(--ss-primary), var(--ss-accent));
            color: #fff; font-size: 1.1rem; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; box-shadow: 0 0 20px rgba(37,99,235,.4);
        }
        .ss-step-connector {
            width: 2px; height: 40px;
            background: linear-gradient(to bottom, rgba(37,99,235,.5), transparent);
            margin: 0.5rem auto;
        }
        .ss-step-card {
            background: var(--ss-dark-3);
            border: 1px solid var(--ss-border);
            border-radius: 16px; padding: 1.75rem;
            text-align: left;
            transition: border-color .3s, transform .3s, box-shadow .3s;
            height: 100%;
        }
        .ss-step-card:hover {
            border-color: rgba(37,99,235,.4);
            transform: translateY(-4px);
            box-shadow: 0 20px 60px rgba(0,0,0,.3);
        }
        .ss-step-card h3 { font-size: 1.15rem; font-weight: 700; margin: .75rem 0 .5rem; }
        .ss-step-card p { font-size: .9rem; color: var(--ss-muted); line-height: 1.6; margin: 0; }
        .ss-step-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; margin-bottom: .75rem;
        }

        /* ── Features ───────────────────────────────────────── */
        .ss-feature-card {
            background: var(--ss-dark-2);
            border: 1px solid var(--ss-border);
            border-radius: 16px; padding: 1.75rem;
            height: 100%;
            transition: border-color .3s, transform .3s, box-shadow .3s;
            position: relative; overflow: hidden;
        }
        .ss-feature-card::before {
            content: ''; position: absolute;
            top: 0; left: 0; right: 0; height: 1px;
            background: linear-gradient(90deg, transparent, rgba(37,99,235,.5), transparent);
            opacity: 0; transition: opacity .3s;
        }
        .ss-feature-card:hover { border-color: rgba(37,99,235,.3); transform: translateY(-4px); }
        .ss-feature-card:hover::before { opacity: 1; }
        .ss-feature-card:hover { box-shadow: 0 20px 60px rgba(0,0,0,.3); }
        .ss-feature-icon {
            width: 52px; height: 52px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; margin-bottom: 1rem;
        }
        .ss-feature-card h3 { font-size: 1.05rem; font-weight: 700; margin-bottom: .4rem; }
        .ss-feature-card p { font-size: .875rem; color: var(--ss-muted); line-height: 1.6; margin: 0; }

        /* ── File types ─────────────────────────────────────── */
        .ss-files { background: var(--ss-dark-2); }
        .ss-filetype {
            display: flex; flex-direction: column; align-items: center;
            gap: .5rem; padding: 1rem;
            background: var(--ss-dark-3); border: 1px solid var(--ss-border);
            border-radius: 12px;
            transition: transform .2s, border-color .2s;
        }
        .ss-filetype:hover { transform: translateY(-3px); border-color: rgba(37,99,235,.3); }
        .ss-filetype-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
        }
        .ss-filetype span { font-size: .75rem; font-weight: 600; color: var(--ss-muted); }

        /* ── Pricing ─────────────────────────────────────────── */
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
            font-size: .72rem; font-weight: 700; text-transform: uppercase;
            padding: .25rem .75rem; border-radius: 999px;
            background: var(--ss-primary); color: #fff;
            margin-bottom: 1rem; display: inline-block;
        }
        .ss-price-amount {
            font-size: 2.5rem; font-weight: 900;
            letter-spacing: -.03em; line-height: 1;
        }
        .ss-price-period { font-size: .85rem; color: var(--ss-muted); margin-left: .25rem; }
        .ss-price-desc { font-size: .875rem; color: var(--ss-muted); margin: .5rem 0 1.5rem; }
        .ss-price-feature {
            display: flex; align-items: flex-start; gap: .6rem;
            font-size: .875rem; margin-bottom: .6rem; color: var(--ss-muted);
        }
        .ss-price-feature i { color: var(--ss-green); font-size: 1rem; flex-shrink: 0; margin-top: .05rem; }
        .ss-price-feature.dim i { color: var(--ss-muted); }
        .btn-pricing-primary {
            background: var(--ss-primary); color: #fff; border: none;
            width: 100%; padding: .75rem; border-radius: 10px;
            font-weight: 700; font-size: .95rem;
            text-decoration: none; display: block; text-align: center;
            transition: background .15s, transform .1s;
            margin-top: auto;
        }
        .btn-pricing-primary:hover { background: var(--ss-primary-d); color: #fff; transform: translateY(-1px); }
        .btn-pricing-outline {
            background: transparent; color: var(--ss-text);
            border: 1px solid var(--ss-border);
            width: 100%; padding: .75rem; border-radius: 10px;
            font-weight: 600; font-size: .95rem;
            text-decoration: none; display: block; text-align: center;
            transition: background .15s;
            margin-top: auto;
        }
        .btn-pricing-outline:hover { background: rgba(255,255,255,.05); color: var(--ss-text); }

        /* ── Open Source section ─────────────────────────────── */
        .ss-oss {
            background: linear-gradient(160deg, rgba(37,99,235,.08) 0%, transparent 60%);
            border-top: 1px solid var(--ss-border);
            border-bottom: 1px solid var(--ss-border);
        }

        /* ── Developer card ──────────────────────────────────── */
        .ss-dev-card {
            background: var(--ss-dark-2);
            border: 1px solid var(--ss-border);
            border-radius: 20px; padding: 2.5rem;
            display: flex; flex-direction: column; align-items: center;
            text-align: center; max-width: 520px; margin: 0 auto;
        }
        .ss-dev-avatar {
            width: 80px; height: 80px; border-radius: 50%;
            background: linear-gradient(135deg, var(--ss-primary), var(--ss-accent));
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; font-weight: 900; color: #fff;
            margin-bottom: 1.25rem;
            box-shadow: 0 0 30px rgba(37,99,235,.4);
        }
        .ss-dev-name { font-size: 1.3rem; font-weight: 800; margin-bottom: .25rem; }
        .ss-dev-title { font-size: .875rem; color: var(--ss-muted); margin-bottom: 1.5rem; }
        .ss-dev-links { display: flex; flex-direction: column; gap: .65rem; width: 100%; }
        .ss-dev-link {
            display: flex; align-items: center; gap: .75rem;
            padding: .65rem 1rem; border-radius: 10px;
            background: var(--ss-dark-3); border: 1px solid var(--ss-border);
            text-decoration: none; color: var(--ss-text);
            font-size: .875rem; transition: border-color .15s, background .15s;
        }
        .ss-dev-link:hover { border-color: rgba(37,99,235,.4); background: rgba(37,99,235,.08); color: var(--ss-text); }
        .ss-dev-link i { color: var(--ss-primary); font-size: 1.1rem; width: 20px; text-align: center; }

        /* ── Footer ──────────────────────────────────────────── */
        .ss-footer {
            background: var(--ss-dark-2);
            border-top: 1px solid var(--ss-border);
            padding: 3rem 1.5rem;
        }
        .ss-footer-brand {
            font-size: 1.2rem; font-weight: 900; color: #fff;
            margin-bottom: .5rem;
        }
        .ss-footer-brand .accent { color: var(--ss-primary); }
        .ss-footer-tagline { font-size: .8rem; color: var(--ss-muted); margin-bottom: 1.25rem; }
        .ss-footer-links { list-style: none; display: flex; gap: 1.5rem; flex-wrap: wrap; }
        .ss-footer-links a { color: var(--ss-muted); text-decoration: none; font-size: .875rem; transition: color .15s; }
        .ss-footer-links a:hover { color: var(--ss-primary); }
        .ss-footer-bottom {
            border-top: 1px solid var(--ss-border);
            margin-top: 2rem; padding-top: 1.5rem;
            font-size: .78rem; color: var(--ss-muted);
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;
        }

        /* ── Animations ──────────────────────────────────────── */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fade-up { animation: fadeInUp .7s ease both; }
        .delay-1 { animation-delay: .1s; }
        .delay-2 { animation-delay: .2s; }
        .delay-3 { animation-delay: .3s; }
        .delay-4 { animation-delay: .4s; }

        @media (max-width: 768px) {
            .ss-nav { padding: .75rem 1rem; }
            .ss-nav-links .hide-mobile { display: none; }
            .ss-section { padding: 4rem 1rem; }
        }
    </style>
</head>
<body>

{{-- ── NAVBAR ─────────────────────────────────────────────── --}}
<nav class="ss-nav" id="navbar">
    <a href="/" class="ss-brand"><span class="accent">School</span>Share</a>
    <div class="ss-nav-links">
        <a href="#how-it-works" class="hide-mobile">How it works</a>
        <a href="#features" class="hide-mobile">Features</a>
        <a href="#pricing" class="hide-mobile">Pricing</a>
        <a href="{{ config('schoolshare.branding.github_url') }}" target="_blank" class="hide-mobile">
            <i class="bi bi-github me-1"></i>GitHub
        </a>
        @auth
            <a href="{{ route('dashboard') }}" class="btn-nav-cta">Dashboard</a>
        @else
            <a href="{{ route('login') }}" class="hide-mobile">Log in</a>
            <a href="{{ route('register') }}" class="btn-nav-cta">Get started free</a>
        @endauth
    </div>
</nav>

{{-- ── HERO ─────────────────────────────────────────────────── --}}
<section class="ss-hero">
    <div class="ss-hero-bg"></div>
    <div class="ss-hero-content">
        <div class="ss-badge fade-up">
            <span class="dot"></span>
            Now open source · Made in Ethiopia 🇪🇹
        </div>
        <h1 class="fade-up delay-1">
            Version control<br>
            <span class="highlight">for everyone.</span><br>
            No terminal required.
        </h1>
        <p class="lead fade-up delay-2">
            Save, track, and restore your school work — essays, presentations, code, anything —
            from home, school, or anywhere. Like Git, but designed for real students.
        </p>
        <div class="ss-hero-ctas fade-up delay-3">
            @auth
            <a href="{{ route('dashboard') }}" class="btn-hero-primary">
                <i class="bi bi-grid-1x2"></i> Go to Dashboard
            </a>
            @else
            <a href="{{ route('register') }}" class="btn-hero-primary">
                <i class="bi bi-rocket-takeoff"></i> Get started — it's free
            </a>
            @endauth
            <a href="{{ config('schoolshare.branding.github_url') }}" target="_blank" class="btn-hero-secondary">
                <i class="bi bi-github"></i> View on GitHub
            </a>
        </div>

        {{-- App Mockup --}}
        <div class="ss-hero-mockup fade-up delay-4">
            <div class="ss-mockup-window">
                <div class="ss-mockup-bar">
                    <div class="ss-mockup-dot" style="background:#ef4444;"></div>
                    <div class="ss-mockup-dot" style="background:#f59e0b;"></div>
                    <div class="ss-mockup-dot" style="background:#10b981;"></div>
                    <div class="ms-2" style="font-size:.75rem;color:var(--ss-muted);">SchoolShare — My History Essay</div>
                </div>
                <div class="ss-mockup-body">
                    @php
                    $files = [
                        ['icon'=>'bi-file-earmark-word','color'=>'rgba(37,99,235,.2)','iconColor'=>'#60a5fa','name'=>'history_essay_final.docx','meta'=>'2.3 MB · Edited 2 hours ago','badge'=>'Latest','bcolor'=>'rgba(16,185,129,.15)','btcolor'=>'#6ee7b7'],
                        ['icon'=>'bi-file-earmark-slides','color'=>'rgba(245,158,11,.15)','iconColor'=>'#fcd34d','name'=>'presentation_slides.pptx','meta'=>'5.1 MB · Checkpoint #3','badge'=>'v3','bcolor'=>'rgba(37,99,235,.15)','btcolor'=>'#93c5fd'],
                        ['icon'=>'bi-file-earmark-pdf','color'=>'rgba(239,68,68,.15)','iconColor'=>'#fca5a5','name'=>'references.pdf','meta'=>'890 KB · Unchanged','badge'=>'v1','bcolor'=>'rgba(100,116,139,.15)','btcolor'=>'#94a3b8'],
                        ['icon'=>'bi-image','color'=>'rgba(16,185,129,.15)','iconColor'=>'#6ee7b7','name'=>'map_of_ethiopia.jpg','meta'=>'1.1 MB · Added in v2','badge'=>'v2','bcolor'=>'rgba(245,158,11,.15)','btcolor'=>'#fcd34d'],
                    ];
                    @endphp
                    @foreach($files as $f)
                    <div class="ss-mockup-row">
                        <div class="ss-mockup-icon" style="background:{{ $f['color'] }};">
                            <i class="bi {{ $f['icon'] }}" style="color:{{ $f['iconColor'] }};"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="ss-mockup-filename">{{ $f['name'] }}</div>
                            <div class="ss-mockup-meta">{{ $f['meta'] }}</div>
                        </div>
                        <div class="ss-checkpoint-badge" style="background:{{ $f['bcolor'] }};color:{{ $f['btcolor'] }};">
                            {{ $f['badge'] }}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ── HOW IT WORKS ─────────────────────────────────────────── --}}
<section class="ss-section ss-how" id="how-it-works">
    <div class="container">
        <div class="text-center">
            <div class="ss-section-label">Simple as 1, 2, 3</div>
            <h2 class="ss-section-title">How SchoolShare works</h2>
            <p class="ss-section-sub">No complicated setup. No terminal commands. Just upload your files and SchoolShare handles the rest.</p>
        </div>
        <div class="row g-4 justify-content-center">
            @php
            $steps = [
                ['num'=>'1','icon'=>'bi-folder-plus','bg'=>'rgba(37,99,235,.15)','ic'=>'#60a5fa','title'=>'Create a Project','desc'=>'Give your project a name — like "History Essay" or "Science Fair". Projects work for any type of file: documents, presentations, code, images, anything.'],
                ['num'=>'2','icon'=>'bi-cloud-upload','bg'=>'rgba(6,182,212,.15)','ic'=>'#22d3ee','title'=>'Upload & Checkpoint','desc'=>'Drag and drop your files. Add a short message like "Added chapter 2" and save a checkpoint. This is your snapshot — like hitting Save, but better.'],
                ['num'=>'3','icon'=>'bi-arrow-repeat','bg'=>'rgba(16,185,129,.15)','ic'=>'#34d399','title'=>'Access Anywhere','desc'=>'At school? Download your latest version. At home? Upload new changes. Made a mistake? Restore any previous checkpoint in one click.'],
            ];
            @endphp
            @foreach($steps as $i => $s)
            <div class="col-md-4">
                <div class="ss-step-card">
                    <div class="ss-step-icon" style="background:{{ $s['bg'] }};">
                        <i class="bi {{ $s['icon'] }}" style="color:{{ $s['ic'] }};font-size:1.3rem;"></i>
                    </div>
                    <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:{{ $s['ic'] }};margin-bottom:.5rem;">Step {{ $s['num'] }}</div>
                    <h3>{{ $s['title'] }}</h3>
                    <p>{{ $s['desc'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── FEATURES ─────────────────────────────────────────────── --}}
<section class="ss-section" id="features">
    <div class="container">
        <div class="text-center">
            <div class="ss-section-label">Everything you need</div>
            <h2 class="ss-section-title">Built for students, by a student</h2>
            <p class="ss-section-sub">Every feature was designed around how real high school students actually work.</p>
        </div>
        <div class="row g-4">
            @php
            $features = [
                ['icon'=>'bi-clock-history','bg'=>'rgba(37,99,235,.15)','ic'=>'#60a5fa','title'=>'Full Version History','desc'=>'Every checkpoint is saved forever. Go back to any version of your work — from yesterday or a month ago. Never lose progress again.'],
                ['icon'=>'bi-eye','bg'=>'rgba(6,182,212,.15)','ic'=>'#22d3ee','title'=>'In-Browser Viewer','desc'=>'View Word documents, PDFs, PowerPoints, images, and code files right in your browser — no download needed.'],
                ['icon'=>'bi-pencil-square','bg'=>'rgba(16,185,129,.15)','ic'=>'#34d399','title'=>'In-Browser Editor','desc'=>'Edit text files, code, and Markdown directly in the browser with syntax highlighting. Save your changes as a new checkpoint.'],
                ['icon'=>'bi-file-earmark-zip','bg'=>'rgba(245,158,11,.15)','ic'=>'#fbbf24','title'=>'Download as ZIP','desc'=>'Download any checkpoint as a ZIP file — perfect for printing, submitting assignments, or working offline.'],
                ['icon'=>'bi-arrow-counterclockwise','bg'=>'rgba(239,68,68,.15)','ic'=>'#f87171','title'=>'One-Click Restore','desc'=>'Click "Restore" on any old checkpoint to bring it back as your current version. No data is ever deleted.'],
                ['icon'=>'bi-people','bg'=>'rgba(168,85,247,.15)','ic'=>'#c084fc','title'=>'Collaboration','desc'=>'Invite classmates to a project as editors or viewers. See who uploaded what and when in the activity feed.'],
            ];
            @endphp
            @foreach($features as $f)
            <div class="col-md-6 col-lg-4">
                <div class="ss-feature-card">
                    <div class="ss-feature-icon" style="background:{{ $f['bg'] }};">
                        <i class="bi {{ $f['icon'] }}" style="color:{{ $f['ic'] }};"></i>
                    </div>
                    <h3>{{ $f['title'] }}</h3>
                    <p>{{ $f['desc'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── FILE TYPES ───────────────────────────────────────────── --}}
<section class="ss-section ss-files">
    <div class="container">
        <div class="text-center">
            <div class="ss-section-label">Works with everything</div>
            <h2 class="ss-section-title">Every file type welcome</h2>
            <p class="ss-section-sub">SchoolShare is not just for code. It's for every kind of school work.</p>
        </div>
        @php
        $types = [
            ['icon'=>'bi-file-earmark-word','bg'=>'rgba(37,99,235,.2)','ic'=>'#60a5fa','label'=>'Word'],
            ['icon'=>'bi-file-earmark-pdf','bg'=>'rgba(239,68,68,.2)','ic'=>'#f87171','label'=>'PDF'],
            ['icon'=>'bi-file-earmark-slides','bg'=>'rgba(245,158,11,.2)','ic'=>'#fbbf24','label'=>'PowerPoint'],
            ['icon'=>'bi-file-earmark-spreadsheet','bg'=>'rgba(16,185,129,.2)','ic'=>'#34d399','label'=>'Excel'],
            ['icon'=>'bi-image','bg'=>'rgba(168,85,247,.2)','ic'=>'#c084fc','label'=>'Images'],
            ['icon'=>'bi-camera-video','bg'=>'rgba(6,182,212,.2)','ic'=>'#22d3ee','label'=>'Video'],
            ['icon'=>'bi-music-note','bg'=>'rgba(239,68,68,.15)','ic'=>'#f87171','label'=>'Audio'],
            ['icon'=>'bi-file-earmark-code','bg'=>'rgba(37,99,235,.2)','ic'=>'#818cf8','label'=>'Code'],
            ['icon'=>'bi-markdown','bg'=>'rgba(100,116,139,.2)','ic'=>'#94a3b8','label'=>'Markdown'],
            ['icon'=>'bi-file-earmark-text','bg'=>'rgba(251,191,36,.15)','ic'=>'#fbbf24','label'=>'Text'],
        ];
        @endphp
        <div class="row g-3 justify-content-center">
            @foreach($types as $t)
            <div class="col-4 col-sm-3 col-md-2 col-lg-auto">
                <div class="ss-filetype">
                    <div class="ss-filetype-icon" style="background:{{ $t['bg'] }};">
                        <i class="bi {{ $t['icon'] }}" style="color:{{ $t['ic'] }};"></i>
                    </div>
                    <span>{{ $t['label'] }}</span>
                </div>
            </div>
            @endforeach
        </div>
        <p class="text-center mt-4" style="font-size:.875rem;color:var(--ss-muted);">
            Up to <strong style="color:var(--ss-text);">100 MB per file</strong> &middot;
            <strong style="color:var(--ss-text);">3 GB total</strong> storage per student &middot;
            <strong style="color:var(--ss-text);">15 projects</strong> on the free plan
        </p>
    </div>
</section>

{{-- ── PRICING ──────────────────────────────────────────────── --}}
<section class="ss-section" id="pricing">
    <div class="container">
        <div class="text-center">
            <div class="ss-section-label">Fair pricing</div>
            <h2 class="ss-section-title">Free for students. Always.</h2>
            <p class="ss-section-sub">The core features are free forever. Schools and organizations that want a custom branded version can get a commercial license.</p>
        </div>
        <div class="row g-4 justify-content-center align-items-stretch">
            {{-- Free Plan --}}
            <div class="col-md-4">
                <div class="ss-pricing-card d-flex flex-column h-100">
                    <div>
                        <div style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--ss-muted);margin-bottom:.75rem;">Student</div>
                        <div class="ss-price-amount">Free<span class="ss-price-period">/ forever</span></div>
                        <div class="ss-price-desc">Everything you need for school. No credit card.</div>
                    </div>
                    <div class="flex-grow-1">
                        @foreach(['15 Projects','3 GB Storage','100 MB per file','Full version history','In-browser viewer & editor','Download as ZIP','Collaboration (up to 5 people)'] as $f)
                        <div class="ss-price-feature"><i class="bi bi-check-circle-fill"></i> {{ $f }}</div>
                        @endforeach
                    </div>
                    <a href="{{ route('register') }}" class="btn-pricing-outline mt-3">Get started free</a>
                </div>
            </div>

            {{-- Pro Plan --}}
            <div class="col-md-4">
                <div class="ss-pricing-card featured d-flex flex-column h-100">
                    <div>
                        <div class="ss-pricing-badge">Most Popular</div>
                        <div style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--ss-muted);margin-bottom:.75rem;">Pro</div>
                        <div class="ss-price-amount">Contact<span class="ss-price-period"> us</span></div>
                        <div class="ss-price-desc">For power users who need more storage and projects.</div>
                    </div>
                    <div class="flex-grow-1">
                        @foreach(['Everything in Student','20 Projects','10 GB Storage','Priority support','Early access to new features'] as $f)
                        <div class="ss-price-feature"><i class="bi bi-check-circle-fill"></i> {{ $f }}</div>
                        @endforeach
                    </div>
                    <a href="mailto:mahizeki037@gmail.com?subject=SchoolShare Pro Inquiry" class="btn-pricing-primary mt-3">
                        Contact to upgrade
                    </a>
                </div>
            </div>

            {{-- White-label --}}
            <div class="col-md-4">
                <div class="ss-pricing-card d-flex flex-column h-100">
                    <div>
                        <div style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--ss-muted);margin-bottom:.75rem;">White-label</div>
                        <div class="ss-price-amount">Custom</div>
                        <div class="ss-price-desc">Run SchoolShare under your own school's brand. No EthioNext branding.</div>
                    </div>
                    <div class="flex-grow-1">
                        @foreach(['Everything in Pro','Custom branding & logo','Self-hosted or managed','School-wide deployment','Commercial license key','Developer support'] as $f)
                        <div class="ss-price-feature"><i class="bi bi-check-circle-fill"></i> {{ $f }}</div>
                        @endforeach
                    </div>
                    <a href="mailto:mahizeki037@gmail.com?subject=SchoolShare White-label License" class="btn-pricing-outline mt-3">
                        Get a license
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ── OPEN SOURCE ──────────────────────────────────────────── --}}
<section class="ss-section ss-oss">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="ss-section-label">Open Source</div>
                <h2 class="ss-section-title" style="max-width:480px;">
                    The code is open.<br>The branding is protected.
                </h2>
                <p style="color:var(--ss-muted);line-height:1.7;margin-bottom:1.5rem;">
                    SchoolShare is released under the <strong style="color:var(--ss-text);">SchoolShare Community License (SCL v1.0)</strong>.
                    You can use it, modify it, and learn from it for free —
                    as long as the EthioNext credit stays visible.
                </p>
                <p style="color:var(--ss-muted);line-height:1.7;margin-bottom:2rem;">
                    Want to remove the branding? Get a <a href="#pricing" style="color:var(--ss-primary);">Commercial License</a>.
                    Schools and developers are welcome!
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="{{ config('schoolshare.branding.github_url') }}" target="_blank" class="btn-hero-primary">
                        <i class="bi bi-github"></i> View on GitHub
                    </a>
                    <a href="{{ config('schoolshare.branding.github_url') }}/blob/main/CONTRIBUTING.md" target="_blank" class="btn-hero-secondary">
                        <i class="bi bi-code-slash"></i> Contribute
                    </a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="ss-feature-card" style="padding:1.5rem;">
                    <div style="font-size:.72rem;font-weight:600;color:var(--ss-muted);margin-bottom:1rem;font-family:monospace;">
                        LICENSE.md — SCL v1.0
                    </div>
                    @php
                    $can = ['Use, modify, and self-host for free','Fork and contribute back','Study the code and learn from it'];
                    $cannot = ['Remove EthioNext branding (without commercial license)','Sell as your own product','Build a competing commercial product'];
                    @endphp
                    <div style="margin-bottom:1rem;">
                        <div style="font-size:.75rem;font-weight:700;color:#34d399;margin-bottom:.5rem;">✅ YOU MAY</div>
                        @foreach($can as $c)
                        <div style="font-size:.82rem;color:var(--ss-muted);padding:.25rem 0;">· {{ $c }}</div>
                        @endforeach
                    </div>
                    <div>
                        <div style="font-size:.75rem;font-weight:700;color:#f87171;margin-bottom:.5rem;">❌ WITHOUT A LICENSE</div>
                        @foreach($cannot as $c)
                        <div style="font-size:.82rem;color:var(--ss-muted);padding:.25rem 0;">· {{ $c }}</div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ── DEVELOPER / CONTACT ──────────────────────────────────── --}}
<section class="ss-section" id="contact">
    <div class="container">
        <div class="text-center">
            <div class="ss-section-label">The Developer</div>
            <h2 class="ss-section-title">Built by a student, for students</h2>
            <p class="ss-section-sub">
                Questions, feedback, or want to collaborate?
                SchoolShare is a personal project — reach out directly.
            </p>
        </div>
        <div class="ss-dev-card">
            <div class="ss-dev-avatar">M</div>
            <div class="ss-dev-name">Mahi Zeki Mukhtar</div>
            <div class="ss-dev-title">Developer & Founder · EthioNext</div>
            <div class="ss-dev-links">
                <a href="mailto:mahizeki037@gmail.com" class="ss-dev-link" id="contact-email">
                    <i class="bi bi-envelope-fill"></i>
                    <span>mahizeki037@gmail.com</span>
                </a>
                <a href="tel:+251992194042" class="ss-dev-link" id="contact-phone">
                    <i class="bi bi-telephone-fill"></i>
                    <span>+251 992 194 042</span>
                </a>
                <a href="{{ config('schoolshare.branding.github_url') }}" target="_blank" class="ss-dev-link" id="contact-github">
                    <i class="bi bi-github"></i>
                    <span>github.com/mz-mukhtar/School-Share</span>
                </a>
                <a href="https://ethionext.com.et" target="_blank" class="ss-dev-link" id="contact-website">
                    <i class="bi bi-globe2"></i>
                    <span>ethionext.com.et</span>
                </a>
            </div>
        </div>
    </div>
</section>

{{-- ── FOOTER ───────────────────────────────────────────────── --}}
<footer class="ss-footer">
    <div class="container">
        <div class="row align-items-start g-4">
            <div class="col-md-4">
                <div class="ss-footer-brand"><span class="accent">School</span>Share</div>
                <div class="ss-footer-tagline">Version control for everyone. No terminal required.</div>
                <div style="font-size:.78rem;color:var(--ss-muted);">
                    by <a href="https://ethionext.com.et" style="color:var(--ss-primary);text-decoration:none;">EthioNext</a> ·
                    Developed by Mahi Zeki Mukhtar
                </div>
            </div>
            <div class="col-md-2">
                <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--ss-muted);margin-bottom:.75rem;">Product</div>
                <ul class="ss-footer-links flex-column">
                    <li><a href="#how-it-works">How it works</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#pricing">Pricing</a></li>
                    <li><a href="{{ route('register') }}">Sign up free</a></li>
                </ul>
            </div>
            <div class="col-md-2">
                <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--ss-muted);margin-bottom:.75rem;">Open Source</div>
                <ul class="ss-footer-links flex-column">
                    <li><a href="{{ config('schoolshare.branding.github_url') }}" target="_blank">GitHub</a></li>
                    <li><a href="{{ config('schoolshare.branding.github_url') }}/blob/main/LICENSE.md" target="_blank">License</a></li>
                    <li><a href="{{ config('schoolshare.branding.github_url') }}/blob/main/CONTRIBUTING.md" target="_blank">Contributing</a></li>
                    <li><a href="{{ config('schoolshare.branding.github_url') }}/blob/main/CHANGELOG.md" target="_blank">Changelog</a></li>
                </ul>
            </div>
            <div class="col-md-2">
                <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--ss-muted);margin-bottom:.75rem;">Self-hosting</div>
                <ul class="ss-footer-links flex-column">
                    <li><a href="{{ config('schoolshare.branding.github_url') }}/blob/main/SELF_HOSTING.md" target="_blank">cPanel Guide</a></li>
                    <li><a href="{{ config('schoolshare.branding.github_url') }}/blob/main/SECURITY.md" target="_blank">Security</a></li>
                    <li><a href="{{ config('schoolshare.branding.github_url') }}/blob/main/docs/ARCHITECTURE.md" target="_blank">Architecture</a></li>
                </ul>
            </div>
            <div class="col-md-2">
                <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--ss-muted);margin-bottom:.75rem;">Contact</div>
                <ul class="ss-footer-links flex-column">
                    <li><a href="mailto:mahizeki037@gmail.com">Email</a></li>
                    <li><a href="tel:+251992194042">+251 992 194 042</a></li>
                    <li><a href="https://ethionext.com.et" target="_blank">EthioNext</a></li>
                </ul>
            </div>
        </div>
        <div class="ss-footer-bottom">
            <span>© {{ date('Y') }} EthioNext · SchoolShare · All rights reserved</span>
            <span>
                Made with ❤️ in Ethiopia &nbsp;·&nbsp;
                <a href="{{ config('schoolshare.branding.github_url') }}/blob/main/LICENSE.md" target="_blank">SCL v1.0 License</a>
            </span>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Sticky navbar on scroll
    const nav = document.getElementById('navbar');
    window.addEventListener('scroll', () => {
        nav.classList.toggle('scrolled', window.scrollY > 20);
    }, { passive: true });
</script>
</body>
</html>
