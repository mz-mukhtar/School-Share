<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

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

        $project->collaborators()->syncWithoutDetaching([
            $user->id => ['role' => $request->input('role')]
        ]);

        return back()->with('success', 'Collaborator added.');
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
