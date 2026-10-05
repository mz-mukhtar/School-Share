<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $status = DB::transaction(function () use ($follower, $user): string {
            User::query()->whereKey([$follower->id, $user->id])->lockForUpdate()->get();
            $isFollowing = $follower->following()->where('following_id', $user->id)->exists();

            if ($isFollowing) {
                $follower->following()->detach($user->id);

                return 'unfollowed';
            }

            $follower->following()->syncWithoutDetaching([$user->id]);
            \App\Models\Activity::log('follow', $follower, $user);

            return 'followed';
        });

        return response()->json([
            'status' => $status,
            'followers_count' => $user->followers()->count(),
            'following_count' => $user->following()->count(),
        ]);
    }
}
