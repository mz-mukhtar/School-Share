@extends('layouts.app')

@section('content')
<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center pt-5 pb-5 text-center">
    <div>
        <h1 class="display-1 fw-bold text-primary mb-3">404</h1>
        <h2 class="mb-4">Page Not Found</h2>
        <p class="text-muted mb-4 lead">The page or resource you are looking for doesn't exist or has been moved.</p>
        <a href="{{ url('/') }}" class="btn btn-primary rounded-pill px-4">
            <i class="bi bi-house me-2"></i> Return Home
        </a>
    </div>
</div>
@endsection
