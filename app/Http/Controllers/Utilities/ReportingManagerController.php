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

        $managers = DB::table('tbl_emp_official_info')
            ->where('role', 'Reporting Manager')
            ->select(['hrid', 'firstname', 'middlename', 'lastname', 'extension', 'office'])
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get()
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

    private function authorizeAdmin(): void
    {
        $auth = Auth::user();

        if (! $auth) {
            abort(401);
        }

        $role = strtolower(trim((string) ($auth->role ?? '')));
        $allowedRoles = [
            'system admin',
            'system administrator',
            'admin',
            'hr manager',
            'hr officer',
            'hr',
            'ao manager',
            'ao',
            'sds manager',
            'sds',
        ];

        if (! in_array($role, $allowedRoles, true)) {
            abort(403);
        }
    }
}
