<?php

namespace App\Http\Controllers;

use App\EmailOtpService;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Checkpoint;
use App\Models\CheckpointFileSnapshot;
use App\Models\CheckpointFolderSnapshot;
use App\Models\EmailOtpToken;
use App\Models\FileVersion;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\StoredBlob;
use App\Models\User;
use App\StorageLifecycle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request, EmailOtpService $otp): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar_path'] = $path;
        }

        $emailChanged = DB::transaction(function () use ($user, $validated): bool {
            $account = User::query()->lockForUpdate()->findOrFail($user->id);
            $account->fill($validated);
            $emailChanged = $account->isDirty('email');
            if ($emailChanged) {
                $account->email_verified_at = null;
                EmailOtpToken::where('user_id', $account->id)->delete();
            }
            $account->save();

            return $emailChanged;
        });

        if ($emailChanged) {
            $sent = $otp->send($user->refresh());

            return Redirect::route('otp.verify.show')->with(
                $sent ? 'success' : 'warning',
                $sent ? 'Your email was changed. Verify the new address using the code sent to it.' : 'Your email was changed, but code delivery failed. Use Resend code to try again.'
            );
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request, StorageLifecycle $storage): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        abort_if(
            $user->projects()->get()->contains(fn (Project $project): bool => $project->hasForeignFolderReferences()),
            409,
            'Your projects have invalid folder references. Repair them before deleting your account.'
        );

        $storage->run(function (StorageLifecycle $storage) use ($user) {
            $foreignVersions = FileVersion::whereHas('projectFile.project', fn ($query) => $query->where('user_id', '!=', $user->id))
                ->where(function ($query) use ($user) {
                    $query->where('storage_path', 'like', "projects/{$user->id}/%")
                        ->orWhereIn('storage_path', StoredBlob::where('billing_user_id', $user->id)->select('storage_path'))
                        ->orWhereHas('checkpoint', fn ($query) => $query->where('user_id', $user->id));
                });
            foreach ($foreignVersions->distinct()->pluck('storage_path') as $path) {
                $blob = $storage->adopt($path);
                abort_if($blob->billing_user_id === $user->id, 409, 'You still own stored files in other projects. Have their owners remove those files before deleting your account.');
            }

            Checkpoint::query()
                ->where('user_id', $user->id)
                ->whereHas('project', fn ($query) => $query->where('user_id', '!=', $user->id))
                ->update(['user_id' => null]);

            $files = ProjectFile::withTrashed()->whereHas('project', fn ($query) => $query->where('user_id', $user->id))->with('versions')->get();
            $storage->deleteFiles($files, function () use ($user): void {
                $ownedProjectIds = $user->projects()->select('id');

                CheckpointFileSnapshot::query()
                    ->whereHas('checkpoint', fn ($query) => $query->whereIn('project_id', $ownedProjectIds))
                    ->delete();
                CheckpointFolderSnapshot::query()
                    ->whereHas('checkpoint', fn ($query) => $query->whereIn('project_id', $ownedProjectIds))
                    ->delete();

                $user->delete();
            });
        });
        if ($user->avatar_path && ! Storage::disk('public')->delete($user->avatar_path)) {
            report(new \RuntimeException('Deleted account avatar cleanup failed.'));
        }
        Auth::logoutCurrentDevice();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
