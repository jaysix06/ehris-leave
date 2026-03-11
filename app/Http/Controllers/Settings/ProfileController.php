<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Services\ActivityLogService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $changes = [];

        $validated = $request->validated();
        unset($validated['avatar']);
        $request->user()->fill($validated);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
            $changes[] = 'email';
        }

        // Track other changes
        if ($request->user()->isDirty('firstname')) {
            $changes[] = 'firstname';
        }
        if ($request->user()->isDirty('lastname')) {
            $changes[] = 'lastname';
        }
        if ($request->user()->isDirty('fullname')) {
            $changes[] = 'fullname';
        }
        if ($request->user()->isDirty('middlename')) {
            $changes[] = 'middlename';
        }
        if ($request->user()->isDirty('extname')) {
            $changes[] = 'extname';
        }
        if ($request->user()->isDirty('personal_email')) {
            $changes[] = 'personal_email';
        }

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                $ext = 'jpg';
            }
            $filename = (string) $user->userId.'_'.time().'.'.$ext;
            $path = $file->storeAs('avatars', $filename, 'public');
            if ($path !== false) {
                $request->user()->avatar = $filename;
                $changes[] = 'avatar';
            }
        }

        $request->user()->save();

        // Log the profile update
        if (! empty($changes)) {
            ActivityLogService::logUpdate(
                'User',
                "Updated user: {$user->email}"
            );
        }

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();
        $userEmail = $user->email;
        $userId = $user->userId;

        // Log the deletion BEFORE logging out and deleting
        ActivityLogService::logDelete(
            'User',
            "Deleted user account: {$userEmail}",
            $userId
        );

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
