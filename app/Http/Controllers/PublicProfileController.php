<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Project;
use Illuminate\Http\Request;

class PublicProfileController extends Controller
{
    public function show($username)
    {
        $user = User::where('username', $username)->firstOrFail();

        // Get public projects
        $projects = $user->projects()
            ->where('visibility', 'public')
            ->withCount('checkpoints')
            ->latest()
            ->paginate(6);
            
        // Contribution graph data (last 365 days)
        $contributions = \Illuminate\Support\Facades\DB::table('activities')
            ->select(\Illuminate\Support\Facades\DB::raw('date(created_at) as date'), \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(365))
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Recent activity for the sidebar
        $recentActivities = \App\Models\Activity::where('user_id', $user->id)
            ->latest('created_at')
            ->limit(10)
            ->get();

        return view('users.show', compact('user', 'projects', 'recentActivities', 'contributions'));
    }

    public function followers($username)
    {
        $user = User::where('username', $username)->firstOrFail();
        $connections = $user->followers()->paginate(20);
        $type = 'followers';
        
        return view('users.connections', compact('user', 'connections', 'type'));
    }

    public function following($username)
    {
        $user = User::where('username', $username)->firstOrFail();
        $connections = $user->following()->paginate(20);
        $type = 'following';
        
        return view('users.connections', compact('user', 'connections', 'type'));
    }
}
