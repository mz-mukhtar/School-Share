<?php

namespace App\Http\Controllers;

use App\Mail\AdminUpgradeReminderMail;
use App\Mail\UpgradeInstructionsMail;
use App\Models\UpgradeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class UpgradeController extends Controller
{
    /**
     * Show the upgrade request form for a given plan.
     */
    public function show(Request $request, string $plan)
    {
        abort_unless(in_array($plan, ['pro', 'custom']), 404);

        $user = auth()->user();

        // If already on this plan, redirect back
        if ($user->plan === $plan) {
            return redirect()->route('pricing')->with('warning', 'You are already on the ' . ucfirst($plan) . ' plan!');
        }

        // Check if they already have a pending request
        $existing = UpgradeRequest::where('user_id', $user->id)
            ->where('requested_plan', $plan)
            ->where('status', 'pending')
            ->first();

        return view('upgrade.checkout', compact('plan', 'user', 'existing'));
    }

    /**
     * Submit an upgrade request and send the payment instructions email.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plan'          => ['required', 'in:pro,custom'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
        ]);

        $user = auth()->user();

        // Prevent duplicate pending requests
        $existing = UpgradeRequest::where('user_id', $user->id)
            ->where('requested_plan', $validated['plan'])
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return redirect()->route('pricing')
                ->with('warning', 'You already have a pending upgrade request. Please check your email for payment instructions.');
        }

        // Create the request record
        $upgradeRequest = UpgradeRequest::create([
            'user_id'        => $user->id,
            'requested_plan' => $validated['plan'],
            'billing_cycle'  => $validated['billing_cycle'],
            'status'         => 'pending',
        ]);

        // 1. Send payment instructions to the user
        Mail::to($user->email)->send(new UpgradeInstructionsMail($user, $upgradeRequest));

        // 2. Send reminder to the admin
        Mail::to('mahizeki037@gmail.com')->send(new AdminUpgradeReminderMail($upgradeRequest));

        return redirect()->route('pricing')
            ->with('success', '🎉 Great! We\'ve sent you an email with complete payment instructions. Once you pay, send your screenshot to payment@ethionext.com.et and we\'ll upgrade your account within 24–48 hours!');
    }
}
