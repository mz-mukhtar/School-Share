<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UpgradeRequest;
use App\Notifications\UpgradeRequestApproved;
use App\Notifications\UpgradeRequestRejected;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UpgradeRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');
        
        $requests = UpgradeRequest::with('user', 'processedBy')
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20);

        return view('admin.upgrades.index', compact('requests', 'status'));
    }

    public function approve(Request $request, UpgradeRequest $upgradeRequest)
    {
        DB::transaction(function () use ($upgradeRequest): void {
            $request = UpgradeRequest::query()->lockForUpdate()->findOrFail($upgradeRequest->id);
            abort_unless($request->isPending(), 409, 'This upgrade request has already been processed.');
            $request->update([
                'status' => 'approved',
                'processed_at' => now(),
                'processed_by' => auth()->id(),
            ]);
            $request->user()->update(['plan' => $request->requested_plan]);
        });

        // Optionally send an in-app notification if we had a Notification class set up,
        // or just simple Database Notification:
        /*
        $upgradeRequest->user->notify(new UpgradeRequestApproved($upgradeRequest));
        */

        return back()->with('success', "Request approved. {$upgradeRequest->user->name} is now on the {$upgradeRequest->requested_plan} plan.");
    }

    public function reject(Request $request, UpgradeRequest $upgradeRequest)
    {
        $validated = $request->validate([
            'admin_notes' => 'required|string|max:1000'
        ]);

        DB::transaction(function () use ($upgradeRequest, $validated): void {
            $request = UpgradeRequest::query()->lockForUpdate()->findOrFail($upgradeRequest->id);
            abort_unless($request->isPending(), 409, 'This upgrade request has already been processed.');
            $request->update([
                'status' => 'rejected',
                'admin_notes' => $validated['admin_notes'],
                'processed_at' => now(),
                'processed_by' => auth()->id(),
            ]);
        });

        // Notify user about rejection (if implemented)
        /*
        $upgradeRequest->user->notify(new UpgradeRequestRejected($upgradeRequest));
        */

        return back()->with('success', "Request rejected. The user can submit a new request if needed.");
    }
}
