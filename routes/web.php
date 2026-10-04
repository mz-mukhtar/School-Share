<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Landing page — redirects logged-in users to dashboard
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('welcome');
})->name('home');

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Explore (stub for Phase 3)
Route::get('/explore', function () {
    return view('explore.index');
})->name('explore');

// Authenticated routes
Route::middleware('auth')->group(function () {

    // Profile (Breeze default)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Projects (stub — will be fully implemented in Phase 3)
    Route::resource('projects', App\Http\Controllers\ProjectController::class);

});

require __DIR__.'/auth.php';
