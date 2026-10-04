<?php

namespace App\Http\Controllers;

use App\Models\Checkpoint;
use App\Models\Comment;
use Illuminate\Http\Request;
use App\Models\Activity;

class CommentController extends Controller
{
    /**
     * Store a newly created comment in storage.
     */
    public function store(Request $request, Checkpoint $checkpoint)
    {
        $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $project = $checkpoint->project;

        // Authorize: Must be able to view the project
        if ($project->visibility === 'private' && auth()->id() !== $project->user_id) {
            abort(403, 'This project is private.');
        }

        $comment = $checkpoint->comments()->create([
            'user_id' => auth()->id(),
            'body' => $request->input('body'),
        ]);

        // Log activity
        Activity::log('comment_created', auth()->user(), $comment, [
            'project_name' => $project->name,
            'project_slug' => $project->slug,
            'checkpoint_id' => $checkpoint->id,
            'checkpoint_title' => $checkpoint->title,
        ]);

        return redirect()->back()->with('success', 'Comment posted successfully!');
    }

    /**
     * Remove the specified comment from storage.
     */
    public function destroy(Comment $comment)
    {
        // Authorize: Can only delete if user is comment owner or project owner
        $project = $comment->checkpoint->project;
        
        if (auth()->id() !== $comment->user_id && auth()->id() !== $project->user_id) {
            abort(403, 'Unauthorized action.');
        }

        $comment->delete();

        return redirect()->back()->with('success', 'Comment deleted.');
    }
}
