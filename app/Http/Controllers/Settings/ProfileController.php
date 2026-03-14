<?php

namespace App\Http\Controllers\Settings;

use App\Events\AuthUserProfileUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\Role;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\File;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $departments = [];
        if (Schema::hasTable('tbl_department')) {
            $departments = DB::table('tbl_department')
                ->select('department_id as id', 'department_name as name')
                ->orderBy('department_name')
                ->get()
                ->map(fn ($r) => ['id' => (int) $r->id, 'name' => (string) $r->name])
                ->values()
                ->all();
        }

        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'departments' => $departments,
            'roles' => Role::roleNames(),
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
        $avatarFile = $request->file('avatar');
        unset($validated['avatar'], $validated['name']);
        // Normalize empty personal_email to null so it can be cleared
        if (array_key_exists('personal_email', $validated) && trim((string) $validated['personal_email']) === '') {
            $validated['personal_email'] = null;
        }
        $request->user()->fill($validated);

        // Recompute fullname from parts when name parts are present
        if (array_key_exists('firstname', $validated) || array_key_exists('lastname', $validated)
            || array_key_exists('middlename', $validated) || array_key_exists('extname', $validated)) {
            $full = trim(implode(' ', array_filter([
                $request->user()->firstname ?? '',
                $request->user()->middlename ?? '',
                $request->user()->lastname ?? '',
                $request->user()->extname ?? '',
            ])));
            $request->user()->fullname = $full !== '' ? $full : $request->user()->fullname;
        }

        // When firstname/lastname change for active user, keep DepEd email in sync
        if ($user->active && ($request->user()->isDirty('firstname') || $request->user()->isDirty('lastname'))) {
            $request->user()->email = $this->buildOfficialDepedEmail(
                (string) ($request->user()->firstname ?? ''),
                (string) ($request->user()->lastname ?? ''),
                $user->userId
            );
            $changes[] = 'email';
        }

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
            $changes[] = 'email';
        }
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
        if ($request->user()->isDirty('role')) {
            $changes[] = 'role';
        }
        if ($request->user()->isDirty('job_title')) {
            $changes[] = 'job_title';
        }
        if ($request->user()->isDirty('department_id')) {
            $changes[] = 'department_id';
        }

        if ($avatarFile && $avatarFile->isValid()) {
            $file = $avatarFile;
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                $ext = 'jpg';
            }
            // Store avatar under /public so it can be served reliably on Windows.
            // We persist the relative path into tbl_user.avatar (e.g. "uploads/avatars/1_123.jpg").
            $dir = public_path('uploads/avatars');
            if (! File::exists($dir)) {
                File::makeDirectory($dir, 0755, true, true);
            }
            $filename = (string) $user->userId.'_'.time().'.'.$ext;
            $file->move($dir, $filename);
            $request->user()->avatar = 'uploads/avatars/'.$filename;
            $changes[] = 'avatar';
        }

        $request->user()->save();

        if (! empty($changes)) {
            ActivityLogService::logUpdate(
                'User',
                "Updated user: {$user->email}"
            );
            $request->user()->refresh();
            broadcast(new AuthUserProfileUpdated($request->user()));
        }

        return to_route('profile.edit');
    }

    private function buildOfficialDepedEmail(string $firstname, string $lastname, int $userId): string
    {
        $first = preg_replace('/[^a-z0-9]/i', '', Str::lower(trim($firstname)));
        $last = preg_replace('/[^a-z0-9]/i', '', Str::lower(trim($lastname)));
        if ($first === '' && $last === '') {
            return 'user'.$userId.'@deped.gov.ph';
        }
        $base = $first !== '' && $last !== '' ? $first.'.'.$last : ($first ?: $last);

        return $base.'@deped.gov.ph';
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
