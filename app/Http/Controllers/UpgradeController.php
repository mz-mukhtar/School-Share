<?php

namespace App\Http\Controllers;

use App\Mail\AdminUpgradeReminderMail;
use App\Mail\UpgradeInstructionsMail;
use App\MailDeliveryFailure;
use App\Models\UpgradeRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class UpgradeController extends Controller
{
    /**
     * Show the upgrade request form for a given plan.
     */
    public function show(Request $request, string $plan): View|RedirectResponse
    {
        abort_unless(in_array($plan, ['pro', 'custom']), 404);

        $user = $request->user();

        // If already on this plan, redirect back
        if ($user->plan === $plan) {
            return redirect()->route('pricing')->with('warning', 'You are already on the '.ucfirst($plan).' plan!');
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
            'plan' => ['required', 'in:pro,custom'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
        ]);

        $user = $request->user();

        $upgradeRequest = DB::transaction(function () use ($user, $validated): UpgradeRequest {
            $account = User::query()->lockForUpdate()->findOrFail($user->id);
            $existing = $account->upgradeRequests()
                ->where('requested_plan', $validated['plan'])
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            return $existing ?? $account->upgradeRequests()->create([
                'requested_plan' => $validated['plan'],
                'billing_cycle' => $validated['billing_cycle'],
                'status' => 'pending',
            ]);
        }, 3);

        if (! $this->sendInstructions($user, $upgradeRequest)) {
            return redirect()->route('pricing')->with('warning', 'Your upgrade request is saved, but email delivery failed. Submit the same request again to retry delivery.');
        }

        return redirect()->route('pricing')
            ->with('success', 'Your upgrade request is saved and payment instructions were sent.');
    }

    private function sendInstructions(User $user, UpgradeRequest $upgradeRequest): bool
    {
        try {
            Mail::to($user->email)->send(new UpgradeInstructionsMail($user, $upgradeRequest));
            Mail::to('mahizeki037@gmail.com')->send(new AdminUpgradeReminderMail($upgradeRequest));
            $upgradeRequest->update([
                'mail_delivery_status' => 'sent',
                'mail_delivery_error' => null,
                'mail_sent_at' => now(),
            ]);

            return true;
        } catch (Throwable $exception) {
            $failure = new MailDeliveryFailure('Upgrade instructions', $exception::class);
            report($failure);
            $upgradeRequest->update([
                'mail_delivery_status' => 'failed',
                'mail_delivery_error' => $failure->getMessage(),
            ]);

            return false;
        }
    }
}
