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
        $query = Project::with('owner')->public()->latest();

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('subject_tag', 'like', "%{$search}%");
            });
        }

        $projects = $query->paginate(12);

        return view('explore.index', compact('projects'));
    }
}
