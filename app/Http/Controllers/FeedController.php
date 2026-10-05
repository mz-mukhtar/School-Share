<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Checkpoint;
use App\Models\Comment;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    /**
     * Display a listing of activities from users the current user follows.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $followingIds = $user->following()->pluck('users.id')->toArray();
        $followingIds[] = $user->id;

        $activities = Activity::whereIn('user_id', $followingIds)
            ->visibleTo($user)
            ->with(['user', 'subject' => fn (MorphTo $subjects) => $subjects->morphWith([
                Checkpoint::class => ['project'],
                Comment::class => ['checkpoint.project'],
            ])])
            ->latest('created_at')
            ->latest('id')
            ->paginate(20);

        return view('feed.index', compact('activities'));
    }
}
