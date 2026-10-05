<?php

use App\Http\Controllers\CheckpointController;
use App\Http\Controllers\CollaboratorController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExploreController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\ForkController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\ZipController;
use App\Http\Controllers\UpgradeController;
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

// About
Route::get('/about', function () {
    return view('about');
})->name('about');

// Pricing
Route::get('/pricing', function () {
    return view('pricing');
})->name('pricing');

// Guide
Route::get('/guide', function () {
    return view('guide');
})->name('guide');

// Sitemap
Route::get('/sitemap.xml', function () {
    return response()->view('sitemap')->header('Content-Type', 'text/xml');
});

// Public User Profile
Route::get('/u/{username}', [PublicProfileController::class, 'show'])->name('profile.public');
Route::get('/u/{username}/followers', [PublicProfileController::class, 'followers'])->name('profile.followers');
Route::get('/u/{username}/following', [PublicProfileController::class, 'following'])->name('profile.following');

// ── Authenticated pages ───────────────────────────────────────────────────────

// Dashboard — requires login AND verified email
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'otp.verified'])
    ->name('dashboard');

Route::middleware(['auth', 'otp.verified'])->group(function () {

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
    Route::get('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');

    // Feed
    Route::get('/feed', [FeedController::class, 'index'])->name('feed');

    // Follow System
    Route::post('/users/{user}/follow', [FollowController::class, 'toggle'])->name('users.follow');

    // Explore (Public projects search)
    Route::get('/explore', [ExploreController::class, 'index'])->name('explore');

    Route::get('/upgrades/{plan}', [UpgradeController::class, 'show'])->name('upgrade.show');
    Route::post('/upgrades', [UpgradeController::class, 'store'])->name('upgrade.store');

    // Profile (Breeze default)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Projects
    Route::resource('projects', ProjectController::class);
    Route::post('projects/{project}/star', [ProjectController::class, 'toggleStar'])->name('projects.star');
    Route::post('projects/{project}/fork', [ForkController::class, 'store'])->middleware('throttle:forks')->name('projects.fork');

    // Checkpoints (nested under projects)
    Route::prefix('projects/{project}')->name('projects.')->scopeBindings()->group(function () {
        Route::get('/checkpoints', [CheckpointController::class, 'index'])->name('checkpoints.index');
        Route::get('/checkpoints/create', [CheckpointController::class, 'create'])->name('checkpoints.create');
        Route::post('/checkpoints', [CheckpointController::class, 'store'])->middleware('throttle:uploads')->name('checkpoints.store');
        Route::get('/checkpoints/{checkpoint}', [CheckpointController::class, 'show'])->name('checkpoints.show');
        Route::post('/checkpoints/{checkpoint}/restore', [CheckpointController::class, 'restore'])->middleware('throttle:uploads')->name('checkpoints.restore');
        Route::delete('/checkpoints/{checkpoint}', [CheckpointController::class, 'destroy'])->name('checkpoints.destroy');
        Route::post('/checkpoints/{checkpoint}/comments', [CommentController::class, 'store'])->name('checkpoints.comments.store');
        Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

        // Files (nested under projects, not checkpoints — for easy access by file ID)
        Route::get('/files/{file}', [FileController::class, 'show'])->middleware('throttle:file-views')->name('files.show');
        Route::get('/files/{file}/download', [FileController::class, 'download'])->middleware('throttle:downloads')->name('files.download');
        Route::get('/files/{file}/diff', [FileController::class, 'diff'])->middleware('throttle:diffs')->name('files.diff');
        Route::put('/files/{file}', [FileController::class, 'update'])->middleware('throttle:editor')->name('files.update');
        Route::delete('/files/{file}', [FileController::class, 'destroy'])->name('files.destroy');
        // Folders
        Route::post('/folders', [FolderController::class, 'store'])->name('folders.store');
        Route::delete('/folders/{folder}', [FolderController::class, 'destroy'])->name('folders.destroy');

        // Zip
        Route::get('/download-zip', [ZipController::class, 'downloadProject'])->middleware('throttle:downloads')->name('download-zip');

        // Collaborators
        Route::post('/collaborators', [CollaboratorController::class, 'store'])->name('collaborators.store');
        Route::put('/collaborators/{collaborator}', [CollaboratorController::class, 'update'])->name('collaborators.update');
        Route::delete('/collaborators/{collaborator}', [CollaboratorController::class, 'destroy'])->name('collaborators.destroy');
    });

});

require __DIR__.'/auth.php';
