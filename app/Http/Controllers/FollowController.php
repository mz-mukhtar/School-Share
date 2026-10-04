<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    /**
     * Toggle follow status for a user.
     */
    public function toggle(User $user)
    {
        $follower = auth()->user();
        
        if ($follower->id === $user->id) {
            return response()->json(['error' => 'You cannot follow yourself.'], 400);
        }

        $isFollowing = $follower->following()->where('following_id', $user->id)->exists();

        if ($isFollowing) {
            $follower->following()->detach($user->id);
            $status = 'unfollowed';
        } else {
            $follower->following()->attach($user->id);
            $status = 'followed';
            
            // Record activity
            \App\Models\Activity::log('follow', $follower, $user);
        }

        return response()->json([
            'status' => $status,
            'followers_count' => $user->followers()->count(),
            'following_count' => $user->following()->count(),
        ]);
    }
}
