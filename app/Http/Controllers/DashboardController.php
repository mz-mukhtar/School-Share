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

        // Stats
        $projectCount    = $user->projects()->count();
        $checkpointCount = \App\Models\Checkpoint::whereIn(
            'project_id', $user->projects()->select('id')
        )->count();
        $recentProjects  = $user->projects()->withCount('checkpoints')->latest()->take(5)->get();

        return view('dashboard', compact('projectCount', 'checkpointCount', 'recentProjects'));
    }
}
