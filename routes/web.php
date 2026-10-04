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

    // Explore (Public projects search)
    Route::get('/explore', [App\Http\Controllers\ExploreController::class, 'index'])->name('explore');

    // Profile (Breeze default)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Projects
    Route::resource('projects', App\Http\Controllers\ProjectController::class);

    // Checkpoints (nested under projects)
    Route::prefix('projects/{project}')->name('projects.')->group(function () {
        Route::get('/checkpoints',                   [App\Http\Controllers\CheckpointController::class, 'index']  )->name('checkpoints.index');
        Route::get('/checkpoints/create',            [App\Http\Controllers\CheckpointController::class, 'create'] )->name('checkpoints.create');
        Route::post('/checkpoints',                  [App\Http\Controllers\CheckpointController::class, 'store']  )->name('checkpoints.store');
        Route::get('/checkpoints/{checkpoint}',      [App\Http\Controllers\CheckpointController::class, 'show']   )->name('checkpoints.show');
        Route::delete('/checkpoints/{checkpoint}',   [App\Http\Controllers\CheckpointController::class, 'destroy'])->name('checkpoints.destroy');

        // Files (nested under projects, not checkpoints — for easy access by file ID)
        Route::get('/files/{file}',          [App\Http\Controllers\FileController::class, 'show']    )->name('files.show');
        Route::get('/files/{file}/download', [App\Http\Controllers\FileController::class, 'download'])->name('files.download');
        Route::delete('/files/{file}',       [App\Http\Controllers\FileController::class, 'destroy'] )->name('files.destroy');
    });

});

require __DIR__.'/auth.php';
