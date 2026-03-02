<?php

namespace App\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use App\Mail\AccountActivatedMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
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
     * API endpoint: departments list (for Office/School dropdown).
     */
    public function departments()
    {
        $this->authorizeAdmin();

        $departments = DB::table('tbl_department')
            ->select([
                'department_id as id',
                'department_name as name',
            ])
            ->orderBy('department_name')
            ->get();

        return response()->json($departments);
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

        // When an account is activated, treat it as "email verified"
        if (! $wasActive && $user->active && $user->email_verified_at === null) {
            $user->email_verified_at = now();
        }

        $user->save();

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
     * API endpoint: update basic user fields (role, job title, department).
     */
    public function update(Request $request, User $user)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'hrId' => ['nullable', 'integer'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('tbl_user', 'email')->ignore($user->getKey(), $user->getKeyName()),
            ],
            'lastname' => ['nullable', 'string', 'max:255'],
            'firstname' => ['nullable', 'string', 'max:255'],
            'middlename' => ['nullable', 'string', 'max:255'],
            'extname' => ['nullable', 'string', 'max:50'],
            'fullname' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'integer', Rule::exists('tbl_department', 'department_id')],
        ]);

        // Backward-compatible input: accept `hrid` from older clients.
        if ($request->has('hrid') && ! $request->has('hrId')) {
            $data['hrId'] = $request->input('hrid');
        }

        $user->fill($data);
        $user->save();

        $office = null;
        if ($user->department_id) {
            $office = DB::table('tbl_department')
                ->where('department_id', '=', $user->department_id)
                ->value('department_name');
        }

        return response()->json([
            'id' => $user->getKey(),
            'hrid' => $user->hrId,
            'email' => $user->email,
            'lastname' => $user->lastname,
            'firstname' => $user->firstname,
            'middlename' => $user->middlename,
            'extname' => $user->extname,
            'fullname' => $user->fullname,
            'job_title' => $user->job_title,
            'role' => $user->role,
            'active' => (bool) $user->active,
            'department_id' => $user->department_id,
            'office' => $office,
        ]);
    }

    /**
     * API endpoint: delete a user account.
     */
    public function destroy(User $user)
    {
        $this->authorizeAdmin();

        // Prevent an administrator from deleting their own account.
        if (Auth::id() === $user->getKey()) {
            return response()->json([
                'message' => 'You cannot delete your own account.',
            ], 422);
        }

        $id = $user->getKey();
        $user->delete();

        return response()->json([
            'id' => $id,
            'deleted' => true,
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

