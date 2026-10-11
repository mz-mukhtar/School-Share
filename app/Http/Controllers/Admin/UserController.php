<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query();

        // Optional simple search
        if ($search = $request->input('search')) {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Optional filter by plan
        if ($plan = $request->input('plan')) {
            $query->where('plan', $plan);
        }

        $users = $query->latest()->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user): View
    {
        $user->load('projects', 'upgradeRequests');

        return view('admin.users.show', compact('user'));
    }

    public function updatePlan(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'plan' => ['required', Rule::in(array_keys(config('schoolshare.plans')))],
        ]);

        $user->update([
            'plan' => $validated['plan'],
        ]);

        return back()->with('success', "Updated {$user->name}'s plan to {$validated['plan']}.");
    }
}
