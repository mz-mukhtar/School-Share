@extends('layouts.app')

@section('content')
<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center pt-5 pb-5 text-center">
    <div>
        <h1 class="display-1 fw-bold text-danger mb-3">403</h1>
        <h2 class="mb-4">Access Denied</h2>
        <p class="text-muted mb-4 lead">You do not have permission to access this page or resource.</p>
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}" class="btn btn-outline-secondary rounded-pill px-4 me-2">
            <i class="bi bi-arrow-left me-2"></i> Go Back
        </a>
        <a href="{{ url('/') }}" class="btn btn-primary rounded-pill px-4">
            <i class="bi bi-house me-2"></i> Return Home
        </a>
    </div>
</div>
@endsection
