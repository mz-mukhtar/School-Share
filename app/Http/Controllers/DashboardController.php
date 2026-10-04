<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // Touch last_active_at
        $user->timestamps = false;
        $user->last_active_at = now();
        $user->save();
        $user->timestamps = true;

        // Stats — these will be real queries once Project model exists
        $projectCount    = method_exists($user, 'projects') ? $user->projects()->count() : 0;
        $checkpointCount = 0; // will be populated in Phase 3
        $recentProjects  = method_exists($user, 'projects')
            ? $user->projects()->latest()->take(5)->get()
            : collect();

        return view('dashboard', compact('projectCount', 'checkpointCount', 'recentProjects'));
    }
}
