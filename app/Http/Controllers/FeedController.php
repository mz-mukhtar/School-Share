<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    /**
     * Display a listing of activities from users the current user follows.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Get IDs of users the current user is following
        $followingIds = $user->following()->pluck('users.id')->toArray();
        
        // Also include the user's own activities? Or just following. Let's just do following.
        // Actually, maybe show all public activities if following is empty?
        // Let's just stick to standard feed: following + self.
        $followingIds[] = $user->id;

        $activities = Activity::whereIn('user_id', $followingIds)
            ->with(['user', 'subject'])
            ->latest('created_at')
            ->paginate(20);

        return view('feed.index', compact('activities'));
    }
}
