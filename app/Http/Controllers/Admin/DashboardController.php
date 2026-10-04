<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UpgradeRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // --- User stats ---
        $totalUsers   = User::count();
        $activeUsers  = User::where('last_active_at', '>=', now()->subDays(30))->count();
        $planCounts   = User::select('plan', DB::raw('count(*) as total'))
                            ->groupBy('plan')
                            ->pluck('total', 'plan')
                            ->toArray();
        $adminCount   = User::where('is_admin', true)->count();

        // --- Storage ---
        $totalStorageBytes = User::sum('storage_used_bytes');

        // --- Upgrade requests ---
        $pendingUpgrades = UpgradeRequest::where('status', 'pending')->count();
        $totalApproved   = UpgradeRequest::where('status', 'approved')->count();

        // --- New users over last 7 days (sparkline) ---
        $newUsersDaily = User::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(6))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        // --- Recent upgrade requests ---
        $recentUpgrades = UpgradeRequest::with('user')
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalUsers', 'activeUsers', 'planCounts', 'adminCount',
            'totalStorageBytes', 'pendingUpgrades', 'totalApproved',
            'newUsersDaily', 'recentUpgrades',
        ));
    }
}
