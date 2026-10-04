<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\FollowController;
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

// Public User Profile
Route::get('/u/{username}', [App\Http\Controllers\PublicProfileController::class, 'show'])->name('profile.public');
Route::get('/u/{username}/followers', [App\Http\Controllers\PublicProfileController::class, 'followers'])->name('profile.followers');
Route::get('/u/{username}/following', [App\Http\Controllers\PublicProfileController::class, 'following'])->name('profile.following');

// ── Authenticated pages ───────────────────────────────────────────────────────

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {

    // Feed
    Route::get('/feed', [FeedController::class, 'index'])->name('feed');

    // Follow System
    Route::post('/users/{user}/follow', [FollowController::class, 'toggle'])->name('users.follow');

    // Explore (Public projects search)
    Route::get('/explore', [App\Http\Controllers\ExploreController::class, 'index'])->name('explore');

    // Profile (Breeze default)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Projects
    Route::resource('projects', App\Http\Controllers\ProjectController::class);
    Route::post('projects/{project}/star', [App\Http\Controllers\ProjectController::class, 'toggleStar'])->name('projects.star');
    Route::post('projects/{project}/fork', [App\Http\Controllers\ForkController::class, 'store'])->name('projects.fork');

    // Checkpoints (nested under projects)
    Route::prefix('projects/{project}')->name('projects.')->group(function () {
        Route::get('/checkpoints',                   [App\Http\Controllers\CheckpointController::class, 'index']  )->name('checkpoints.index');
        Route::get('/checkpoints/create',            [App\Http\Controllers\CheckpointController::class, 'create'] )->name('checkpoints.create');
        Route::post('/checkpoints',                  [App\Http\Controllers\CheckpointController::class, 'store']  )->name('checkpoints.store');
        Route::get('/checkpoints/{checkpoint}',      [App\Http\Controllers\CheckpointController::class, 'show']   )->name('checkpoints.show');
        Route::post('/checkpoints/{checkpoint}/restore', [App\Http\Controllers\CheckpointController::class, 'restore'])->name('checkpoints.restore');
        Route::delete('/checkpoints/{checkpoint}',   [App\Http\Controllers\CheckpointController::class, 'destroy'])->name('checkpoints.destroy');
        Route::post('/checkpoints/{checkpoint}/comments', [App\Http\Controllers\CommentController::class, 'store'])->name('checkpoints.comments.store');
        Route::delete('/comments/{comment}',         [App\Http\Controllers\CommentController::class, 'destroy'])->name('comments.destroy');

        // Files (nested under projects, not checkpoints — for easy access by file ID)
        Route::get('/files/{file}',          [App\Http\Controllers\FileController::class, 'show']    )->name('files.show');
        Route::get('/files/{file}/download', [App\Http\Controllers\FileController::class, 'download'])->name('files.download');
        Route::get('/files/{file}/diff',     [App\Http\Controllers\FileController::class, 'diff']    )->name('files.diff');
        Route::put('/files/{file}',          [App\Http\Controllers\FileController::class, 'update']  )->name('files.update');
        Route::delete('/files/{file}',       [App\Http\Controllers\FileController::class, 'destroy'] )->name('files.destroy');
        // Folders
        Route::post('/folders', [App\Http\Controllers\FolderController::class, 'store'])->name('folders.store');
        Route::delete('/folders/{folder}', [App\Http\Controllers\FolderController::class, 'destroy'])->name('folders.destroy');

        // Zip
        Route::get('/download-zip', [App\Http\Controllers\ZipController::class, 'downloadProject'])->name('download-zip');

        // Collaborators
        Route::post('/collaborators', [App\Http\Controllers\CollaboratorController::class, 'store'])->name('collaborators.store');
        Route::put('/collaborators/{collaborator}', [App\Http\Controllers\CollaboratorController::class, 'update'])->name('collaborators.update');
        Route::delete('/collaborators/{collaborator}', [App\Http\Controllers\CollaboratorController::class, 'destroy'])->name('collaborators.destroy');
    });

});

require __DIR__.'/auth.php';
