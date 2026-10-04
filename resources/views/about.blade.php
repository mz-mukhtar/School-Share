@extends('layouts.app')

@section('title', 'About SchoolShare')
@section('meta_description', 'Learn about the vision behind SchoolShare, the GitHub for Students built by EthioNext.')

@section('content')
<div class="container py-5" style="max-width: 800px;">
    <div class="text-center mb-5">
        <h1 class="display-5 fw-bold text-white mb-3">About <span style="color:var(--ss-primary);">SchoolShare</span></h1>
        <p class="lead text-muted">A simple, powerful version control system built exclusively for students.</p>
    </div>

    <div class="ss-card mb-5 border-0" style="background:var(--ss-dark-2);">
        <div class="card-body p-4">
            <h3 class="fw-bold mb-4">Our Vision</h3>
            <p style="font-size: 1.1rem; line-height: 1.7; color: var(--ss-text-muted);">
                We built SchoolShare because we noticed a gap. While professional developers have GitHub, students and educators are often stuck emailing zip files back and forth, or dealing with the complexity of setting up a git workflow just to share a simple assignment. 
            </p>
            <p style="font-size: 1.1rem; line-height: 1.7; color: var(--ss-text-muted);">
                <strong>SchoolShare is the "GitHub for Students".</strong> We abstracted away the command line and complex merge conflicts. What's left is a beautifully simple, browser-based repository system where you can upload, edit, track history, and share your projects with a single click.
            </p>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="ss-card h-100">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: rgba(37, 99, 235, 0.1); color: var(--ss-primary);">
                        <i class="bi bi-person fs-4"></i>
                    </div>
                    <h5 class="fw-bold mb-0">The Developer</h5>
                </div>
                <p class="text-muted mb-0">
                    SchoolShare was developed by <strong>Mahi Zeki Mukhtar</strong> under the brand EthioNext. Passionate about education and software, the goal is to make tooling accessible to everyone.
                </p>
                <div class="mt-3">
                    <a href="https://github.com/mz-mukhtar" target="_blank" class="text-decoration-none text-primary"><i class="bi bi-github me-1"></i> GitHub Profile</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="ss-card h-100">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: rgba(6, 182, 212, 0.1); color: var(--ss-accent);">
                        <i class="bi bi-building fs-4"></i>
                    </div>
                    <h5 class="fw-bold mb-0">EthioNext</h5>
                </div>
                <p class="text-muted mb-0">
                    Proudly built by EthioNext. We specialize in creating high-quality, open-source and white-label web applications tailored for specific communities.
                </p>
                <div class="mt-3">
                    <a href="https://ethionext.com.et" target="_blank" class="text-decoration-none text-primary"><i class="bi bi-globe me-1"></i> ethionext.com.et</a>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-5">
        <h4 class="mb-4">Ready to start sharing your work?</h4>
        @auth
            <a href="{{ route('dashboard') }}" class="btn btn-ss-primary btn-lg px-5">Go to Dashboard</a>
        @else
            <a href="{{ route('register') }}" class="btn btn-ss-primary btn-lg px-5 me-2">Create an Account</a>
            <a href="{{ route('login') }}" class="btn btn-ss-outline btn-lg px-5">Log In</a>
        @endauth
    </div>
</div>
@endsection
