<?php

namespace App\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class ReportingManagerController extends Controller
{
    public function index()
    {
        return Inertia::render('Utilities/ReportingManager');
    }

    /**
     * Paginated list of employees with their assigned reporting manager.
     */
    public function api(Request $request)
    {
        $this->authorizeAdmin();

        if (! Schema::hasTable('tbl_emp_official_info')) {
            return response()->json(['data' => [], 'total' => 0]);
        }

        $query = DB::table('tbl_emp_official_info as e')
            ->leftJoin('tbl_department as d', 'e.department_id', '=', 'd.department_id')
            ->select([
                'e.hrid',
                'e.employee_id',
                'e.firstname',
                'e.middlename',
                'e.lastname',
                'e.extension',
                'e.job_title',
                'e.role',
                'e.office',
                'e.reporting_manager',
                'd.department_name',
            ]);

        if ($search = trim((string) $request->get('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('e.firstname', 'like', "%{$search}%")
                    ->orWhere('e.lastname', 'like', "%{$search}%")
                    ->orWhere('e.middlename', 'like', "%{$search}%")
                    ->orWhere('e.employee_id', 'like', "%{$search}%")
                    ->orWhere('e.reporting_manager', 'like', "%{$search}%")
                    ->orWhere('e.office', 'like', "%{$search}%")
                    ->orWhere('d.department_name', 'like', "%{$search}%")
                    ->orWhere('e.hrid', 'like', "%{$search}%");
            });
        }

        $query->orderBy('e.lastname')->orderBy('e.firstname');

        $perPage = max(1, (int) $request->get('per_page', 15));

        return response()->json($query->paginate($perPage)->withQueryString());
    }

    /**
     * DataTables server-side endpoint for employees with reporting manager assignments.
     */
    public function datatables(Request $request)
    {
        $this->authorizeAdmin();

        if (! Schema::hasTable('tbl_emp_official_info')) {
            return response()->json([
                'draw' => (int) $request->get('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $draw = (int) $request->get('draw', 1);
        $start = max(0, (int) $request->get('start', 0));
        $length = max(1, (int) $request->get('length', 10));
        $searchValue = trim((string) $request->input('search.value', ''));
        $orderColumnIndex = (int) $request->input('order.0.column', 0);
        $orderDir = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $baseQuery = DB::table('tbl_emp_official_info as e')
            ->leftJoin('tbl_department as d', 'e.department_id', '=', 'd.department_id');

        $totalRecords = (clone $baseQuery)->count();

        if ($searchValue !== '') {
            $baseQuery->where(function ($q) use ($searchValue) {
                $term = "%{$searchValue}%";
                $q->where('e.firstname', 'like', $term)
                    ->orWhere('e.lastname', 'like', $term)
                    ->orWhere('e.middlename', 'like', $term)
                    ->orWhere('e.employee_id', 'like', $term)
                    ->orWhere('e.reporting_manager', 'like', $term)
                    ->orWhere('e.office', 'like', $term)
                    ->orWhere('d.department_name', 'like', $term)
                    ->orWhere('e.hrid', 'like', $term);
            });
        }

        $filteredRecords = (clone $baseQuery)->count();

        $orderColumns = [
            'e.hrid',
            'e.employee_id',
            'e.lastname',
            'e.job_title',
            'd.department_name',
            'e.reporting_manager',
        ];
        $orderColumn = $orderColumns[max(0, min($orderColumnIndex, count($orderColumns) - 1))];

        $rows = $baseQuery
            ->select([
                'e.hrid',
                'e.employee_id',
                'e.firstname',
                'e.middlename',
                'e.lastname',
                'e.extension',
                'e.job_title',
                'e.role',
                'e.office',
                'e.reporting_manager',
                'd.department_name',
            ])
            ->orderBy($orderColumn, $orderDir)
            ->orderBy('e.lastname')
            ->orderBy('e.firstname')
            ->skip($start)
            ->take($length)
            ->get();

        $data = $rows->map(function ($row, $index) use ($start) {
            return [
                'row_num' => $start + $index + 1,
                'hrid' => (int) ($row->hrid ?? 0),
                'employee_id' => $row->employee_id,
                'firstname' => $row->firstname,
                'middlename' => $row->middlename,
                'lastname' => $row->lastname,
                'extension' => $row->extension,
                'job_title' => $row->job_title,
                'role' => $row->role,
                'office' => $row->office,
                'reporting_manager' => $row->reporting_manager,
                'department_name' => $row->department_name,
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
     * List of employees whose role is 'Reporting Manager' (for the dropdown).
     */
    public function managers()
    {
        $this->authorizeAdmin();

        if (! Schema::hasTable('tbl_emp_official_info')) {
            return response()->json([]);
        }

        $query = DB::table('tbl_emp_official_info')
            ->select(['hrid', 'firstname', 'middlename', 'lastname', 'extension', 'office'])
            ->orderBy('lastname')
            ->orderBy('firstname');

        if (Schema::hasColumn('tbl_emp_official_info', 'is_reporting_manager')) {
            $query->where(function ($q) {
                $q->where('is_reporting_manager', 1)
                    ->orWhere('is_reporting_manager', '1')
                    ->orWhere('is_reporting_manager', true)
                    ->orWhereRaw('LOWER(TRIM(CAST(is_reporting_manager AS CHAR))) IN (?, ?, ?)', ['true', 'yes', 'y']);
            });
        } else {
            $query->where('role', 'Reporting Manager');
        }

        $managers = $query->get()
            ->map(fn ($row) => [
                'hrid' => (int) $row->hrid,
                'name' => trim(implode(' ', array_filter([
                    $row->firstname,
                    $row->middlename,
                    $row->lastname,
                    $row->extension,
                ]))),
                'office' => $row->office,
            ]);

        return response()->json($managers);
    }

    /**
     * Assign a reporting manager to one or more employees.
     */
    public function assign(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'employee_hrids' => ['required', 'array', 'min:1'],
            'employee_hrids.*' => ['required', 'integer'],
            'reporting_manager' => ['required', 'string', 'max:255'],
        ]);

        if (! Schema::hasTable('tbl_emp_official_info') || ! Schema::hasColumn('tbl_emp_official_info', 'reporting_manager')) {
            return response()->json(['message' => 'reporting_manager column not available.'], 422);
        }

        $updated = DB::table('tbl_emp_official_info')
            ->whereIn('hrid', $data['employee_hrids'])
            ->update(['reporting_manager' => $data['reporting_manager']]);

        return response()->json(['updated' => $updated]);
    }

    /**
     * Clear the reporting manager for an employee.
     */
    public function remove(Request $request, int $hrid)
    {
        $this->authorizeAdmin();

        if (! Schema::hasTable('tbl_emp_official_info') || ! Schema::hasColumn('tbl_emp_official_info', 'reporting_manager')) {
            return response()->json(['message' => 'reporting_manager column not available.'], 422);
        }

        DB::table('tbl_emp_official_info')
            ->where('hrid', $hrid)
            ->update(['reporting_manager' => null]);

        return response()->json(['hrid' => $hrid, 'reporting_manager' => null]);
    }

    /**
     * Auto-assign reporting managers to teachers by office (school) first, then department fallback.
     */
    public function autoAssignBySchoolOrDepartment()
    {
        $this->authorizeAdmin();

        if (! Schema::hasTable('tbl_emp_official_info') || ! Schema::hasColumn('tbl_emp_official_info', 'reporting_manager')) {
            return response()->json(['message' => 'reporting_manager column not available.'], 422);
        }

        $rows = DB::table('tbl_emp_official_info')
            ->select([
                'hrid',
                'firstname',
                'middlename',
                'lastname',
                'extension',
                'job_title',
                'role',
                'office',
                'department_id',
                'is_reporting_manager',
            ])
            ->get();

        $employees = $rows->map(function ($row) {
            return (object) [
                'hrid' => (int) ($row->hrid ?? 0),
                'name' => $this->buildEmployeeName($row),
                'job_title' => strtolower(trim((string) ($row->job_title ?? ''))),
                'role' => strtolower(trim((string) ($row->role ?? ''))),
                'office' => strtolower(trim((string) ($row->office ?? ''))),
                'department_id' => $row->department_id,
                'is_reporting_manager' => $this->isReportingManagerValue($row->is_reporting_manager ?? null),
            ];
        });

        $managerCandidates = $employees
            ->filter(fn ($employee) => $employee->hrid > 0 && $employee->is_reporting_manager)
            ->values();

        $teachers = $employees
            ->filter(function ($employee) {
                $isTeacher = str_contains($employee->job_title, 'teacher') || str_contains($employee->role, 'teacher');
                $isPrincipal = str_contains($employee->job_title, 'principal') || str_contains($employee->role, 'principal');

                return $employee->hrid > 0 && $isTeacher && ! $isPrincipal;
            })
            ->values();

        $assigned = 0;
        $unmatched = 0;

        DB::beginTransaction();
        try {
            foreach ($teachers as $teacher) {
                $sameOffice = $managerCandidates
                    ->filter(fn ($candidate) => $candidate->hrid !== $teacher->hrid && $candidate->office !== '' && $candidate->office === $teacher->office)
                    ->values();

                $sameDepartment = $managerCandidates
                    ->filter(fn ($candidate) => $candidate->hrid !== $teacher->hrid && $candidate->department_id !== null && $candidate->department_id === $teacher->department_id)
                    ->values();

                $selectedManager = $this->pickBestManager($sameOffice);

                if (! $selectedManager) {
                    $selectedManager = $this->pickBestManager($sameDepartment);
                }

                if (! $selectedManager) {
                    $unmatched++;

                    continue;
                }

                DB::table('tbl_emp_official_info')
                    ->where('hrid', $teacher->hrid)
                    ->update([
                        'reporting_manager' => $selectedManager->name,
                    ]);

                $assigned++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json([
            'assigned' => $assigned,
            'unmatched' => $unmatched,
            'teachers_scanned' => $teachers->count(),
            'manager_candidates' => $managerCandidates->count(),
        ]);
    }

    private function pickBestManager($candidates)
    {
        if ($candidates->isEmpty()) {
            return null;
        }

        $ranked = $candidates->sortBy(function ($candidate) {
            if (str_contains($candidate->role, 'principal') || str_contains($candidate->job_title, 'principal')) {
                return 1;
            }

            if (str_contains($candidate->role, 'reporting manager') || str_contains($candidate->job_title, 'reporting manager')) {
                return 2;
            }

            if (str_contains($candidate->role, 'head') || str_contains($candidate->job_title, 'head')) {
                return 3;
            }

            return 4;
        })->values();

        return $ranked->first();
    }

    private function buildEmployeeName(object $row): string
    {
        return trim(implode(' ', array_filter([
            (string) ($row->firstname ?? ''),
            (string) ($row->middlename ?? ''),
            (string) ($row->lastname ?? ''),
            (string) ($row->extension ?? ''),
        ])));
    }

    private function isReportingManagerValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'y'], true);
    }

    private function authorizeAdmin(): void
    {
        $auth = Auth::user();

        if (! $auth) {
            abort(401);
        }

        $rawRoles = array_filter([
            (string) ($auth->role ?? ''),
            (string) ($auth->user_role ?? ''),
            (string) ($auth->usertype ?? ''),
            (string) ($auth->user_type ?? ''),
        ], fn ($value) => trim($value) !== '');

        if (empty($rawRoles)) {
            abort(403);
        }

        $normalizedRoles = array_map(fn ($role) => $this->normalizeRole($role), $rawRoles);
        $deniedRoles = ['employee', 'teacher'];

        foreach ($normalizedRoles as $role) {
            if ($role === '') {
                continue;
            }

            if (in_array($role, $deniedRoles, true)) {
                abort(403);
            }
        }
    }

    private function normalizeRole(string $role): string
    {
        $normalized = strtolower(trim($role));
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized) ?? '';
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? '';

        return trim($normalized);
    }
}
