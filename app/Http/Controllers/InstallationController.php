<?php

namespace App\Http\Controllers;

use App\Http\Requests\InstallApplicationRequest;
use App\SchoolShareInstaller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InstallationController extends Controller
{
    public function show(Request $request, SchoolShareInstaller $installer): View
    {
        $unlocked = hash_equals((string) config('installation.token_hash'), (string) $request->session()->get('installation.authorization', ''))
            && (int) $request->session()->get('installation.expires_at', 0) > time();

        return view('installation', ['unlocked' => $unlocked, 'started' => $installer->started(), 'requirements' => $unlocked ? $installer->requirements() : []]);
    }

    public function unlock(Request $request): RedirectResponse
    {
        $validated = $request->validate(['setup_token' => ['required', 'string', 'size:64', 'regex:/\A[a-f0-9]{64}\z/']]);
        if (! hash_equals((string) config('installation.token_hash'), hash('sha256', $validated['setup_token']))) {
            throw ValidationException::withMessages(['setup_token' => 'The setup token is incorrect. Read the private setup-token.txt file in cPanel.']);
        }
        $request->session()->regenerate();
        $request->session()->put(['installation.authorization' => config('installation.token_hash'), 'installation.expires_at' => time() + 1800]);

        return redirect()->route('installation.show');
    }

    public function store(InstallApplicationRequest $request, SchoolShareInstaller $installer): RedirectResponse
    {
        $delivered = $installer->install($request->validated());
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', $delivered
            ? 'SchoolShare is installed. Sign in with your admin account and enter the verification code sent to your email.'
            : 'SchoolShare is installed, but admin email delivery failed. Sign in, check SMTP configuration in the private .env file, and resend your verification code. Do not rerun installation.');
    }
}
