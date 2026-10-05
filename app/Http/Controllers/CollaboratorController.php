<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CollaboratorController extends Controller
{
    public function store(Request $request, Project $project)
    {
        if (auth()->id() !== $project->user_id) {
            abort(403, 'Only the owner can add collaborators.');
        }

        $request->validate([
            'username' => ['required', 'string', 'exists:users,username'],
            'role' => ['required', 'in:editor,viewer'],
        ], [
            'username.exists' => 'User not found.',
        ]);

        $user = User::where('username', $request->input('username'))->firstOrFail();

        if ($user->id === $project->user_id) {
            return back()->with('error', 'You cannot add yourself as a collaborator.');
        }

        $sync = DB::transaction(function () use ($project, $user, $request): array {
            $lockedProject = Project::query()->with('owner')->lockForUpdate()->findOrFail($project->id);
            $alreadyCollaborating = $lockedProject->collaborators()->whereKey($user->id)->exists();
            abort_if(
                ! $alreadyCollaborating && $lockedProject->collaborators()->count() >= $lockedProject->owner->maxCollaborators(),
                422,
                'This project has reached the collaborator limit for its plan.'
            );

            return $lockedProject->collaborators()->syncWithoutDetaching([
                $user->id => ['role' => $request->input('role')],
            ]);
        });

        try {
            Mail::to($user->email)
                ->send(new \App\Mail\ProjectInvitationMail($project, auth()->user(), $user));
            $user->notify(new \App\Notifications\ProjectInvitationNotification($project, auth()->user()));

            $message = empty($sync['attached']) ? 'Collaborator invitation resent.' : 'Collaborator added and invitation sent.';
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('warning', 'The collaborator was saved, but invitation delivery failed. Submit the same collaborator again to retry delivery.');
        }

        return back()->with('success', $message);
    }

    public function update(Request $request, Project $project, User $collaborator)
    {
        if (auth()->id() !== $project->user_id) {
            abort(403);
        }

        $request->validate([
            'role' => ['required', 'in:editor,viewer'],
        ]);

        $project->collaborators()->updateExistingPivot($collaborator->id, [
            'role' => $request->input('role'),
        ]);

        return back()->with('success', 'Collaborator role updated.');
    }

    public function destroy(Project $project, User $collaborator)
    {
        if (auth()->id() !== $project->user_id) {
            abort(403);
        }

        $project->collaborators()->detach($collaborator->id);

        return back()->with('success', 'Collaborator removed.');
    }
}
