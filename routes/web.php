<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// ── Public pages ─────────────────────────────────────────────────────────────

// Landing page — redirects logged-in users to dashboard
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('welcome');
})->name('home');

// Terms of Service
Route::get('/terms', function () {
    return view('terms');
})->name('terms');

// ── Authenticated pages ───────────────────────────────────────────────────────

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {

    // Profile (Breeze default)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Projects (stub — will be fully implemented in Phase 4)
    Route::resource('projects', App\Http\Controllers\ProjectController::class);

});

require __DIR__.'/auth.php';
