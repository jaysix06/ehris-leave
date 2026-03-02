<?php

namespace App\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use App\Mail\AccountActivatedMail;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class UserListController extends Controller
{
    /**
     * Show the User List page (Inertia view).
     */
    public function index()
    {
        return Inertia::render('Utilities/UserList');
    }

    /**
     * API endpoint: paginated users with optional search.
     */
    public function api(Request $request)
    {
        $query = DB::table('tbl_user as u')
            ->leftJoin('tbl_department as d', 'u.department_id', '=', 'd.department_id')
            ->select([
                'u.userId as id',
                'u.hrid as hrid',
                'u.email',
                'u.lastname',
                'u.firstname',
                'u.middlename',
                'u.extname',
                'u.fullname',
                'u.job_title',
                'u.role',
                'u.active',
                'u.date_created',
                'u.department_id',
                'd.department_name as office',
            ])
            // Newest users first: by date_created (if present) then by userId
            ->orderByDesc('u.date_created')
            ->orderByDesc('u.userId');

        if ($search = trim((string) $request->get('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('u.email', 'like', '%'.$search.'%')
                    ->orWhere('u.lastname', 'like', '%'.$search.'%')
                    ->orWhere('u.firstname', 'like', '%'.$search.'%')
                    ->orWhere('u.middlename', 'like', '%'.$search.'%')
                    ->orWhere('u.fullname', 'like', '%'.$search.'%')
                    ->orWhere('u.role', 'like', '%'.$search.'%')
                    ->orWhere('d.department_name', 'like', '%'.$search.'%')
                    ->orWhere('u.hrid', 'like', '%'.$search.'%');
            });
        }

        $perPage = (int) $request->get('per_page', 10);
        $perPage = $perPage > 0 ? $perPage : 10;

        $users = $query->paginate($perPage)->withQueryString();

        return response()->json($users);
    }

    /**
     * API endpoint: toggle active status for a user.
     */
    public function updateStatus(Request $request, User $user)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        $wasActive = (bool) $user->active;
        $user->active = $data['active'];

        // When activating a user that has no HRID yet, default HRID to userId
        if (! $wasActive && $user->active && ($user->hrId === null || $user->hrId === 0)) {
            $user->hrId = $user->getKey();
        }

        $user->save();

        // Log the status update
        $status = $user->active ? 'activated' : 'deactivated';
        ActivityLogService::logUpdate(
            'User',
            "Updated user: {$user->email}"
        );

        // If account has just been activated, notify the user via email
        if (! $wasActive && $user->active && $user->email) {
            try {
                Mail::to($user->email)->send(new AccountActivatedMail([
                    'name' => (string) ($user->fullname ?? $user->name ?? $user->email ?? 'User'),
                    'email' => (string) $user->email,
                    'hrid' => $user->hrId ? (int) $user->hrId : null,
                    'activated_at' => now()->format('Y-m-d H:i'),
                    'sign_in_url' => url('/login'),
                ]));
            } catch (\Throwable $e) {
                // Swallow mail errors so that activation still succeeds
            }
        }

        return response()->json([
            'id' => $user->getKey(),
            'active' => (bool) $user->active,
            'hrid' => $user->hrId,
        ]);
    }

    /**
     * Ensure only administrator-type roles can manage users.
     */
    private function authorizeAdmin(): void
    {
        $auth = Auth::user();

        if (! $auth) {
            abort(401);
        }

        $allowedRoles = [
            'System Admin',
            'HR Manager',
            'AO Manager',
            'SDS Manager',
        ];

        if (! in_array((string) ($auth->role ?? ''), $allowedRoles, true)) {
            abort(403);
        }
    }
}
