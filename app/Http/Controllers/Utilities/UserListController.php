<?php

namespace App\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use App\Mail\AccountActivatedMail;
use App\Mail\PasswordResetMail;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserListController extends Controller
{
    /**
     * Show the User List page (Inertia view).
     */
    public function index()
    {
        return Inertia::render('Utilities/UserList', [
            'roles' => \App\Models\Role::roleNames(),
        ]);
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
                'u.personal_email',
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
     * API endpoint: DataTables server-side processing for User List.
     */
    public function datatables(Request $request)
    {
        $draw = (int) $request->get('draw', 1);
        $start = (int) $request->get('start', 0);
        $length = (int) $request->get('length', 10);
        $searchValue = trim((string) $request->input('search.value', ''));
        $orderColumnIndex = (int) $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';

        // Default: newest users first (by userId desc)
        $columns = ['id', 'hrid', 'personal_email', 'email', 'name', 'role', 'office', 'active', 'actions'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $baseQuery = DB::table('tbl_user as u')
            ->leftJoin('tbl_department as d', 'u.department_id', '=', 'd.department_id')
            ->select([
                'u.userId as id',
                'u.hrid as hrid',
                'u.email',
                'u.personal_email',
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
            ]);

        $totalRecords = DB::table('tbl_user')->count();

        $searchCallback = function ($q) use ($searchValue) {
            $q->where('u.email', 'like', '%'.$searchValue.'%')
                ->orWhere('u.personal_email', 'like', '%'.$searchValue.'%')
                ->orWhere('u.lastname', 'like', '%'.$searchValue.'%')
                ->orWhere('u.firstname', 'like', '%'.$searchValue.'%')
                ->orWhere('u.middlename', 'like', '%'.$searchValue.'%')
                ->orWhere('u.extname', 'like', '%'.$searchValue.'%')
                ->orWhere('u.fullname', 'like', '%'.$searchValue.'%')
                ->orWhere('u.role', 'like', '%'.$searchValue.'%')
                ->orWhere('u.job_title', 'like', '%'.$searchValue.'%')
                ->orWhere('d.department_name', 'like', '%'.$searchValue.'%')
                ->orWhere('u.hrid', 'like', '%'.$searchValue.'%');
        };

        $query = clone $baseQuery;
        if ($searchValue !== '') {
            $query->where($searchCallback);
            $searchLower = strtolower($searchValue);
            if (str_contains($searchLower, 'inactive') || str_contains($searchLower, 'new')) {
                $query->where('u.active', 0);
            } elseif (str_contains($searchLower, 'active')) {
                $query->where('u.active', 1);
            }
        }
        $filteredRecords = $query->count();

        if ($orderColumn === 'name') {
            $baseQuery->orderBy('u.firstname', $orderDir)->orderBy('u.lastname', $orderDir);
        } elseif ($orderColumn === 'id') {
            $baseQuery->orderBy('u.userId', $orderDir);
        } elseif ($orderColumn !== 'actions') {
            $dbCol = $orderColumn === 'office' ? 'd.department_name' : 'u.'.$orderColumn;
            $baseQuery->orderBy($dbCol, $orderDir);
        } else {
            $baseQuery->orderByDesc('u.date_created')->orderByDesc('u.userId');
        }

        if ($searchValue !== '') {
            $baseQuery->where($searchCallback);
            $searchLower = strtolower($searchValue);
            if (str_contains($searchLower, 'inactive') || str_contains($searchLower, 'new')) {
                $baseQuery->where('u.active', 0);
            } elseif (str_contains($searchLower, 'active')) {
                $baseQuery->where('u.active', 1);
            }
        }

        $length = $length > 0 ? $length : 10;
        $users = $baseQuery->skip($start)->take($length)->get();

        $data = $users->map(function ($row) {
            $name = trim(implode(' ', array_filter([
                $row->firstname,
                $row->middlename,
                $row->lastname,
                $row->extname,
            ]))) ?: ($row->fullname ?? $row->email ?? '—');

            $active = (bool) $row->active;
            $displayEmail = $active ? ($row->email ?? $row->personal_email ?? '—') : ($row->personal_email ?? $row->email ?? '—');

            return [
                'id' => $row->id,
                'hrid' => $row->hrid ?? '—',
                'personal_email' => $row->personal_email ?? '—',
                'email' => $displayEmail,
                'name' => $name,
                'role' => $row->role ?? '—',
                'office' => $row->office ?? '—',
                'active' => $active,
                '_raw' => $row,
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    /**
     * API endpoint: create a new user (admin/manual).
     *
     * This is different from self-service registration.
     */
    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'personal_email' => ['required', 'email', 'max:255', Rule::unique('tbl_user', 'personal_email')],
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'middlename' => ['nullable', 'string', 'max:255'],
            'extname' => ['nullable', 'string', 'max:50'],
            'role' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'integer', Rule::exists('tbl_department', 'department_id')],
        ]);

        $fullname = trim(implode(' ', array_filter([
            trim((string) ($data['firstname'] ?? '')),
            trim((string) ($data['middlename'] ?? '')),
            trim((string) ($data['lastname'] ?? '')),
            trim((string) ($data['extname'] ?? '')),
        ]))) ?: (string) $data['personal_email'];

        $user = User::create([
            'fullname' => $fullname,
            'firstname' => $data['firstname'],
            'middlename' => $data['middlename'] ?? null,
            'lastname' => $data['lastname'],
            'extname' => $data['extname'] ?? null,
            'personal_email' => $data['personal_email'],
            // Official DepEd email + password are generated on activation
            'email' => null,
            'password' => null,
            'date_created' => now()->toDateString(),
            'active' => false,
            'role' => $data['role'] ?? 'Employee',
            'job_title' => $data['job_title'] ?? null,
            'department_id' => $data['department_id'] ?? null,
        ]);

        $user->hrId = $user->getKey();
        $user->save();

        ActivityLogService::logCreate('User', "Created user: {$user->personal_email}", $user->getKey());

        return response()->json([
            'id' => $user->getKey(),
            'hrid' => $user->hrId,
            'personal_email' => $user->personal_email,
            'active' => (bool) $user->active,
        ], 201);
    }

    /**
     * Export user list as CSV (admin). Opens in Excel and requires no extra package.
     */
    public function exportExcel(): StreamedResponse
    {
        $this->authorizeAdmin();

        $rows = DB::table('tbl_user as u')
            ->leftJoin('tbl_department as d', 'u.department_id', '=', 'd.department_id')
            ->select([
                'u.userId as id',
                'u.hrid as hrid',
                'u.email',
                'u.personal_email',
                'u.fullname',
                'u.firstname',
                'u.middlename',
                'u.lastname',
                'u.extname',
                'u.role',
                'u.job_title',
                'u.active',
                'd.department_name as office',
                'u.date_created',
            ])
            ->orderByDesc('u.date_created')
            ->orderByDesc('u.userId')
            ->get();

        $filename = 'users-'.now()->format('Y-m-d-His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel

            fputcsv($out, [
                'HRID',
                'Personal Email',
                'Official Email',
                'Name',
                'Role',
                'Job Title',
                'Office/School',
                'Status',
                'Created',
            ]);

            foreach ($rows as $r) {
                $name = trim(implode(' ', array_filter([
                    $r->firstname,
                    $r->middlename,
                    $r->lastname,
                    $r->extname,
                ]))) ?: ($r->fullname ?? $r->personal_email ?? $r->email ?? '');

                fputcsv($out, [
                    $r->hrid ?? '',
                    $r->personal_email ?? '',
                    $r->email ?? '',
                    $name,
                    $r->role ?? '',
                    $r->job_title ?? '',
                    $r->office ?? '',
                    ((bool) $r->active) ? 'Active' : 'Inactive',
                    $r->date_created ?? '',
                ]);
            }

            fclose($out);
        }, $filename, $headers);
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
     * API endpoint: get a single user (for edit modal).
     */
    public function show(User $user)
    {
        $this->authorizeAdmin();

        $row = DB::table('tbl_user as u')
            ->leftJoin('tbl_department as d', 'u.department_id', '=', 'd.department_id')
            ->where('u.userId', $user->getKey())
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
                'u.department_id',
                'd.department_name as office',
            ])
            ->first();

        return response()->json($row);
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

        // When an account is activated, generate official DepEd login and notify user
        if (! $wasActive && $user->active) {
            if ($user->email_verified_at === null) {
                $user->email_verified_at = now();
            }

            // Generate official DepEd email: firstname.lastname@deped.gov.ph (no middle name).
            // Example: "Reagan Jade" + "Balansag" -> "reaganjade.balansag@deped.gov.ph"
            $officialEmail = $this->buildOfficialDepedEmail(
                (string) ($user->firstname ?? ''),
                (string) ($user->lastname ?? ''),
                $user->getKey()
            );

            // Set official login credentials on activation.
            // Default password is fixed so it can be communicated to the user.
            $defaultPassword = '1q2w3e4r5t';

            $user->email = $officialEmail;
            // Hash explicitly so login (Hash::check) works regardless of cast timing.
            $user->password = Hash::make($defaultPassword);
            $user->save();

            $recipient = $user->personal_email ?? $user->email;
            if ($recipient) {
                try {
                    Mail::to($recipient)->send(
                        new AccountActivatedMail([
                            'name' => (string) ($user->fullname ?? $user->name ?? 'User'),
                            'official_email' => $officialEmail,
                            'default_password' => $defaultPassword,
                            'hrid' => $user->hrId ? (int) $user->hrId : null,
                            'activated_at' => now()->format('Y-m-d H:i'),
                            'sign_in_url' => url('/login'),
                        ]),
                    );
                } catch (\Throwable $e) {
                    // Swallow mail errors so that activation still succeeds
                }
            }
        } else {
            $user->save();
        }

        // Log the status update
        ActivityLogService::logUpdate(
            'User',
            "Updated user: {$user->email}"
        );

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

        // When editing first/last name for an active user, recompute official DepEd email
        // so it stays in sync (firstname.lastname@deped.gov.ph, no middle name).
        if ($user->active) {
            $user->email = $this->buildOfficialDepedEmail(
                (string) ($user->firstname ?? ''),
                (string) ($user->lastname ?? ''),
                $user->getKey()
            );
        }

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
     * API endpoint: reset user password to default and send email to personal email.
     *
     * This is an admin/manual reset (different from the "Forgot password" self-service flow).
     */
    public function resetPassword(User $user)
    {
        $this->authorizeAdmin();

        $defaultPassword = '1q2w3e4r5t';
        $user->password = Hash::make($defaultPassword);
        $user->save();

        $recipient = $user->personal_email ?? $user->email;
        if ($recipient) {
            try {
                Mail::to($recipient)->send(
                    new PasswordResetMail([
                        'name' => (string) ($user->fullname ?? $user->name ?? 'User'),
                        'login_email' => (string) ($user->email ?? $recipient),
                        'temporary_password' => $defaultPassword,
                        'sign_in_url' => url('/login'),
                    ]),
                );
            } catch (\Throwable $e) {
                // Swallow mail errors so that reset still succeeds
            }
        }

        ActivityLogService::logUpdate(
            'User',
            "Password reset for user: {$user->email}"
        );

        return response()->json([
            'id' => $user->getKey(),
            'reset' => true,
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

    /**
     * Build official DepEd email from first name and last name only: firstname.lastname@deped.gov.ph
     * Middle name is not used. Example: "Reagan Jade" + "Balansag" -> "reaganjade.balansag@deped.gov.ph"
     *
     * @param  int  $userId  Fallback when no name parts (e.g. "user123@deped.gov.ph")
     */
    private function buildOfficialDepedEmail(string $firstname, string $lastname, int $userId): string
    {
        $first = trim($firstname);
        $last = trim($lastname);

        $firstSegment = preg_replace('/[^a-z0-9]/i', '', $first);
        $lastSegment = preg_replace('/[^a-z0-9]/i', '', $last);

        if ($firstSegment !== '' && $lastSegment !== '') {
            $local = Str::lower($firstSegment.'.'.$lastSegment);
        } elseif ($firstSegment !== '') {
            $local = Str::lower($firstSegment);
        } elseif ($lastSegment !== '') {
            $local = Str::lower($lastSegment);
        } else {
            $local = 'user'.$userId;
        }

        return $local.'@deped.gov.ph';
    }
}
