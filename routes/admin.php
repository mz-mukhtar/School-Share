<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UpgradeRequestController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

// Admin Dashboard with analytics
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// User management
Route::get('/users', [UserController::class, 'index'])->name('users.index');
Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
Route::patch('/users/{user}/plan', [UserController::class, 'updatePlan'])->name('users.updatePlan');

// Upgrade request management
Route::get('/upgrades', [UpgradeRequestController::class, 'index'])->name('upgrades.index');
Route::patch('/upgrades/{upgradeRequest}/approve', [UpgradeRequestController::class, 'approve'])->name('upgrades.approve');
Route::patch('/upgrades/{upgradeRequest}/reject', [UpgradeRequestController::class, 'reject'])->name('upgrades.reject');
