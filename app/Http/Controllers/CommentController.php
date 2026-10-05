<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Checkpoint;
use App\Models\Comment;
use App\Models\Project;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Store a newly created comment in storage.
     */
    public function store(Request $request, Project $project, Checkpoint $checkpoint)
    {
        abort_if($checkpoint->project_id !== $project->id, 404);
        abort_unless($project->hasAccess($request->user()), 403);

        $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

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
    public function destroy(Project $project, Comment $comment)
    {
        abort_if($comment->checkpoint->project_id !== $project->id, 404);
        abort_unless($project->hasAccess(auth()->user()), 403);

        if (auth()->id() !== $comment->user_id && auth()->id() !== $project->user_id) {
            abort(403, 'Unauthorized action.');
        }

        $comment->delete();

        return redirect()->back()->with('success', 'Comment deleted.');
    }
}
