<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ExploreController extends Controller
{
    /**
     * Display a paginated grid of public projects.
     */
    public function index(Request $request)
    {
        $search = $request->input('q');
        $type = $request->input('type', 'projects');

        $projectQuery = Project::with('owner')->public()->latest();
        $userQuery = \App\Models\User::query()->latest();

        if ($search) {
            $projectQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('subject_tag', 'like', "%{$search}%")
                  ->orWhereHas('owner', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                  });
            });

            $userQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $projectCount = $projectQuery->count();
        $userCount = $userQuery->count();

        if ($type === 'users') {
            $users = $userQuery->paginate(12)->appends($request->query());
            $projects = collect(); // empty for this tab
        } else {
            $projects = $projectQuery->paginate(12)->appends($request->query());
            $users = collect(); // empty for this tab
        }

        return view('explore.index', compact('projects', 'users', 'type', 'projectCount', 'userCount'));
    }
}
