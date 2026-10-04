@extends('layouts.app')

@section('title', 'Profile Settings')

@section('content')
<div class="mb-4">
    <h1 class="h3 fw-bold mb-1">Profile Settings</h1>
    <p class="text-muted">Manage your account information and preferences.</p>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        {{-- Profile Information --}}
        <div class="ss-card mb-4">
            <h4 class="h5 fw-bold mb-1">Profile Information</h4>
            <p class="text-muted small mb-4">Update your account's profile information and email address.</p>
            
            <form id="send-verification" method="post" action="{{ route('verification.send') }}">
                @csrf
            </form>

            <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                @csrf
                @method('patch')

                <div class="mb-4 text-center">
                    @if($user->avatar_path)
                        <img src="{{ Storage::url($user->avatar_path) }}" alt="{{ $user->name }}" class="rounded-circle mb-3 object-fit-cover" width="100" height="100">
                    @else
                        <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center text-white fs-1 fw-bold mb-3" style="width: 100px; height: 100px;">
                            {{ substr($user->name, 0, 1) }}
                        </div>
                    @endif
                    <div>
                        <label for="avatar" class="btn btn-sm btn-ss-outline">Change Avatar</label>
                        <input type="file" class="d-none" id="avatar" name="avatar" accept="image/*" onchange="this.form.submit()">
                        @error('avatar')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="name" class="form-label fw-bold">Name</label>
                    <input type="text" class="form-control bg-transparent text-white @error('name') is-invalid @enderror" style="border-color: var(--ss-border);" id="name" name="name" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="username" class="form-label fw-bold">Username</label>
                    <input type="text" class="form-control bg-transparent text-white @error('username') is-invalid @enderror" style="border-color: var(--ss-border);" id="username" name="username" value="{{ old('username', $user->username) }}" autocomplete="username">
                    @error('username')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="bio" class="form-label fw-bold">Bio</label>
                    <textarea class="form-control bg-transparent text-white @error('bio') is-invalid @enderror" style="border-color: var(--ss-border);" id="bio" name="bio" rows="3">{{ old('bio', $user->bio) }}</textarea>
                    @error('bio')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="email" class="form-label fw-bold">Email</label>
                    <input type="email" class="form-control bg-transparent text-white @error('email') is-invalid @enderror" style="border-color: var(--ss-border);" id="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="username">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror

                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                        <div class="mt-2 text-warning small">
                            Your email address is unverified.
                            <button form="send-verification" class="btn btn-link text-warning p-0 ms-1 small" style="text-decoration: underline;">
                                Click here to re-send the verification email.
                            </button>
                        </div>
                        @if (session('status') === 'verification-link-sent')
                            <div class="mt-2 text-success small">
                                A new verification link has been sent to your email address.
                            </div>
                        @endif
                    @endif
                </div>

                <div class="d-flex align-items-center gap-3">
                    <button type="submit" class="btn btn-ss-primary">Save Profile</button>
                    @if (session('status') === 'profile-updated')
                        <span class="text-success small"><i class="bi bi-check-circle me-1"></i>Saved.</span>
                    @endif
                </div>
            </form>
        </div>

        {{-- Update Password --}}
        <div class="ss-card mb-4">
            <h4 class="h5 fw-bold mb-1">Update Password</h4>
            <p class="text-muted small mb-4">Ensure your account is using a long, random password to stay secure.</p>
            
            <form method="post" action="{{ route('password.update') }}">
                @csrf
                @method('put')

                <div class="mb-3">
                    <label for="update_password_current_password" class="form-label fw-bold">Current Password</label>
                    <input type="password" class="form-control bg-transparent text-white @error('current_password', 'updatePassword') is-invalid @enderror" style="border-color: var(--ss-border);" id="update_password_current_password" name="current_password" autocomplete="current-password">
                    @error('current_password', 'updatePassword')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="update_password_password" class="form-label fw-bold">New Password</label>
                    <input type="password" class="form-control bg-transparent text-white @error('password', 'updatePassword') is-invalid @enderror" style="border-color: var(--ss-border);" id="update_password_password" name="password" autocomplete="new-password">
                    @error('password', 'updatePassword')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="update_password_password_confirmation" class="form-label fw-bold">Confirm Password</label>
                    <input type="password" class="form-control bg-transparent text-white @error('password_confirmation', 'updatePassword') is-invalid @enderror" style="border-color: var(--ss-border);" id="update_password_password_confirmation" name="password_confirmation" autocomplete="new-password">
                    @error('password_confirmation', 'updatePassword')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex align-items-center gap-3">
                    <button type="submit" class="btn btn-ss-primary">Update Password</button>
                    @if (session('status') === 'password-updated')
                        <span class="text-success small"><i class="bi bi-check-circle me-1"></i>Saved.</span>
                    @endif
                </div>
            </form>
        </div>

        {{-- Delete Account --}}
        <div class="ss-card border-danger" style="background: rgba(239, 68, 68, 0.05);">
            <h4 class="h5 fw-bold text-danger mb-1">Delete Account</h4>
            <p class="text-muted small mb-4">Once your account is deleted, all of its resources and data will be permanently deleted.</p>
            
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#confirmUserDeletionModal">
                Delete Account
            </button>
        </div>
    </div>
</div>

{{-- Delete Account Modal --}}
<div class="modal fade" id="confirmUserDeletionModal" tabindex="-1" aria-labelledby="confirmUserDeletionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="background: var(--ss-dark-2); border: 1px solid var(--ss-border);">
            <form method="post" action="{{ route('profile.destroy') }}">
                @csrf
                @method('delete')
                
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title fw-bold" id="confirmUserDeletionModalLabel">Are you sure you want to delete your account?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-4">
                        Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.
                    </p>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-bold sr-only">Password</label>
                        <input type="password" class="form-control bg-transparent text-white @error('password', 'userDeletion') is-invalid @enderror" style="border-color: var(--ss-border);" id="password" name="password" placeholder="Password">
                        @error('password', 'userDeletion')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-ss-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($errors->userDeletion->isNotEmpty())
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var myModal = new bootstrap.Modal(document.getElementById('confirmUserDeletionModal'));
            myModal.show();
        });
    </script>
    @endpush
@endif

@endsection
