<?php

namespace App\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeListController extends Controller
{
    public function index(Request $request): Response
    {
        // Get filter options (from tbl_department, same as User List)
        $schools = DB::table('tbl_department')
            ->orderBy('department_name')
            ->pluck('department_name')
            ->values();

        $schoolsGrouped = DB::table('tbl_department')
            ->join('tbl_business', 'tbl_department.business_id', '=', 'tbl_business.BusinessUnitId')
            ->select('tbl_business.BusinessUnitId as district_code', 'tbl_business.BusinessUnit as district_name', 'tbl_department.department_name')
            ->orderBy('tbl_business.BusinessUnitId')
            ->orderBy('tbl_department.department_name')
            ->get()
            ->groupBy('district_code')
            ->map(fn ($rows) => [
                'district' => $rows->first()->district_name,
                'districtCode' => (int) $rows->first()->district_code,
                'offices' => $rows->pluck('department_name')->unique()->sort()->values()->all(),
            ])
            ->values()
            ->all();

        $jobTitles = DB::table('tbl_job_title')
            ->orderBy('job_title')
            ->pluck('job_title')
            ->values();

        $subjects = Employee::whereNotNull('subject_taught')
            ->distinct()
            ->pluck('subject_taught')
            ->map(fn ($v) => is_string($v) ? trim($v) : $v)
            ->filter(fn ($v) => $v !== '' && $v !== null)
            ->sort()
            ->values();

        $gradeLevels = Employee::whereNotNull('grade_level')
            ->distinct()
            ->pluck('grade_level')
            ->map(fn ($v) => is_string($v) ? trim($v) : $v)
            ->filter(fn ($v) => $v !== '' && $v !== null)
            ->sort()
            ->values();

        $employmentStatuses = Employee::whereNotNull('employ_status')
            ->distinct()
            ->pluck('employ_status')
            ->map(fn ($v) => is_string($v) ? trim($v) : $v)
            ->filter(fn ($v) => $v !== '' && $v !== null)
            ->sort()
            ->values();

        $roles = User::whereNotNull('role')
            ->distinct()
            ->pluck('role')
            ->map(fn ($v) => is_string($v) ? trim($v) : $v)
            ->filter(fn ($v) => $v !== '' && $v !== null)
            ->sort()
            ->values();

        $departments = DB::table('tbl_department')
            ->select('department_id as id', 'department_name as name')
            ->orderBy('department_name')
            ->get();

        $businessUnits = DB::table('tbl_business')
            ->select('BusinessUnitId as id', 'BusinessUnit as name')
            ->orderBy('BusinessUnit')
            ->get();

        // Get reporting managers (employees with job titles that suggest management)
        $reportingManagers = Employee::whereNotNull('job_title')
            ->select('hrid', DB::raw("TRIM(CONCAT_WS(' ', firstname, middlename, lastname, extension)) as full_name"), 'job_title')
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get()
            ->map(fn ($emp) => [
                'hrid' => $emp->hrid,
                'name' => $emp->full_name,
                'job_title' => $emp->job_title,
            ]);

        // Get next HRID (highest + 1)
        $nextHrid = Employee::max('hrid') ? (int) Employee::max('hrid') + 1 : 21400;

        return Inertia::render('Utilities/EmployeeList', [
            'filterOptions' => [
                'schools' => $schools,
                'schoolsGrouped' => $schoolsGrouped,
                'jobTitles' => $jobTitles,
                'subjects' => $subjects,
                'gradeLevels' => $gradeLevels,
                'employmentStatuses' => $employmentStatuses,
                'roles' => $roles,
                'departments' => $departments,
                'businessUnits' => $businessUnits,
                'reportingManagers' => $reportingManagers,
                'nextHrid' => $nextHrid,
            ],
            'filters' => $request->only(['school', 'district', 'job_title', 'subject', 'grade_level', 'employment_status', 'salary_grade', 'role', 'search']),
        ]);
    }

    public function datatables(Request $request)
    {
        $query = $this->buildEmployeeQuery($request);

        // DataTables parameters
        $draw = (int) $request->get('draw', 1);
        $start = (int) $request->get('start', 0);
        $length = (int) $request->get('length', 10);

        $showAll = $length === -1;
        $totalRecords = Employee::count();

        // Handle search
        $searchValue = $request->input('search.value');
        if ($searchValue && trim($searchValue) !== '') {
            $searchTerm = trim($searchValue);
            $query->where(function ($q) use ($searchTerm) {
                $q->where('firstname', 'like', '%'.$searchTerm.'%')
                    ->orWhere('lastname', 'like', '%'.$searchTerm.'%')
                    ->orWhere('middlename', 'like', '%'.$searchTerm.'%')
                    ->orWhere('employee_id', 'like', '%'.$searchTerm.'%')
                    ->orWhere('hrid', 'like', '%'.$searchTerm.'%')
                    ->orWhere('job_title', 'like', '%'.$searchTerm.'%')
                    ->orWhere('employ_status', 'like', '%'.$searchTerm.'%');
            });
        }

        $filteredRecords = $query->count();

        if ($showAll) {
            $length = $filteredRecords;
            $start = 0;
        } else {
            $length = $length > 0 ? $length : 10;
        }

        // Handle ordering
        $orderColumnIndex = (int) ($request->input('order.0.column', 0));
        $orderDir = $request->input('order.0.dir', 'asc');

        $columns = [
            'hrid',
            'employee_id',
            'name',
            'job_title',
            'subject_taught',
            'grade_level',
            'office',
            'station_code',
            'salary_grade',
            'salary_step',
            'employ_status',
            'leave_balance',
        ];
        $orderColumn = $columns[$orderColumnIndex] ?? 'hrid';

        if ($orderColumn === 'name') {
            $query->orderBy('firstname', $orderDir)
                ->orderBy('lastname', $orderDir);
        } else {
            $query->orderBy($orderColumn, $orderDir);
        }

        // Apply pagination
        $employees = $query->skip($start)->take($length)->get();

        // Transform to DataTables format
        $data = $employees->map(function ($employee) {
            $fullName = trim(implode(' ', array_filter([
                $employee->firstname,
                $employee->middlename,
                $employee->lastname,
                $employee->extension,
            ])));

            return [
                'id' => $employee->hrid,
                'hrid' => $employee->hrid,
                'employee_id' => $employee->employee_id,
                'name' => $fullName,
                'firstname' => $employee->firstname,
                'middlename' => $employee->middlename,
                'lastname' => $employee->lastname,
                'extension' => $employee->extension,
                'job_title' => $employee->job_title,
                'subject_taught' => $employee->subject_taught,
                'grade_level' => $employee->grade_level,
                'office' => $employee->office_display ?? $employee->office,
                'station_code' => $employee->station_code,
                'salary_grade' => $employee->salary_grade,
                'salary_step' => $employee->salary_step,
                'employ_status' => $employee->employ_status,
                'leave_balance' => $employee->leave_balance,
                '_raw' => $employee,
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    private function buildEmployeeQuery(Request $request)
    {
        $query = Employee::query();
        $empTable = (new Employee)->getTable();

        // Join tbl_department so Office/School column shows department name (same as User List)
        if (Schema::hasColumn($empTable, 'department_id')) {
            $query->leftJoin('tbl_department as d', "{$empTable}.department_id", '=', 'd.department_id')
                ->addSelect("{$empTable}.*", DB::raw('d.department_name as office_display'));
        } else {
            $query->leftJoin('tbl_department as d', DB::raw("CAST({$empTable}.office AS UNSIGNED)"), '=', 'd.department_id')
                ->addSelect("{$empTable}.*", DB::raw('d.department_name as office_display'));
        }

        if ($request->filled('school')) {
            $query->where('d.department_name', $request->school);
        } elseif ($request->filled('district')) {
            $query->where("{$empTable}.business_id", (string) $request->district);
        }

        if ($request->filled('job_title')) {
            $query->where('job_title', $request->job_title);
        }

        if ($request->filled('subject')) {
            $query->where('subject_taught', 'like', '%'.$request->subject.'%');
        }

        if ($request->filled('grade_level')) {
            $query->where('grade_level', $request->grade_level);
        }

        if ($request->filled('employment_status')) {
            $query->where('employ_status', $request->employment_status);
        }

        if ($request->filled('salary_grade')) {
            $query->where('salary_grade', $request->salary_grade);
        }

        if ($request->filled('role')) {
            $query->join('tbl_user', "{$empTable}.hrid", '=', 'tbl_user.hrId')
                ->where('tbl_user.role', $request->role)
                ->select("{$empTable}.*", DB::raw('d.department_name as office_display'));
        }

        return $query;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'hrid' => ['required', 'integer', 'unique:tbl_emp_official_info,hrid'],
            'employee_id' => ['nullable', 'integer'],
            'prefix_name' => ['nullable', 'string', 'max:50'],
            'firstname' => ['required', 'string', 'max:255'],
            'middlename' => ['nullable', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'extension' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone_num' => ['nullable', 'string', 'max:50'],
            'mobile_num' => ['nullable', 'string', 'max:50'],
            'department_id' => ['nullable', 'integer', 'exists:tbl_department,department_id'],
            'business_id' => ['nullable', 'string', 'exists:tbl_business,BusinessUnitId'],
            'reporting_manager' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
            'mode_of_employment' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'employ_status' => ['nullable', 'string', 'max:255'],
            'date_of_original_appointment' => ['nullable', 'date'],
            'date_of_leaving' => ['nullable', 'date'],
            'year_experience' => ['nullable', 'integer'],
        ]);

        // Get department name if department_id is provided
        $departmentName = null;
        if ($data['department_id']) {
            $departmentName = DB::table('tbl_department')
                ->where('department_id', $data['department_id'])
                ->value('department_name');
        }

        // Create employee in tbl_emp_official_info
        $employeeData = [
            'hrid' => $data['hrid'],
            'employee_id' => $data['employee_id'] ?? null,
            'prefix_name' => $data['prefix_name'] ?? null,
            'firstname' => $data['firstname'],
            'middlename' => $data['middlename'] ?? null,
            'lastname' => $data['lastname'],
            'extension' => $data['extension'] ?? null,
            'email' => $data['email'] ?? null,
            'job_title' => $data['job_title'] ?? null,
            'office' => $departmentName,
            'business_id' => $data['business_id'] ?? null,
            'reporting_manager' => $data['reporting_manager'] ?? null,
            'employ_status' => $data['employ_status'] ?? null,
        ];

        // Add fields if they exist in the table
        if (Schema::hasColumn('tbl_emp_official_info', 'date_of_joining')) {
            $employeeData['date_of_joining'] = $data['date_of_original_appointment'] ?? null;
        }
        if (Schema::hasColumn('tbl_emp_official_info', 'year_experience')) {
            $employeeData['year_experience'] = $data['year_experience'] ?? null;
        }

        $employee = Employee::create($employeeData);

        // Create contact info if phone/mobile provided
        if (($data['phone_num'] ?? null) || ($data['mobile_num'] ?? null)) {
            if (Schema::hasTable('tbl_emp_contact_info')) {
                DB::table('tbl_emp_contact_info')->insert([
                    'hrid' => $data['hrid'],
                    'phone_num' => $data['phone_num'] ?? null,
                    'mobile_num' => $data['mobile_num'] ?? null,
                ]);
            }
        }

        // Create corresponding user account (inactive by default)
        $fullname = trim(implode(' ', array_filter([
            $data['firstname'],
            $data['middlename'],
            $data['lastname'],
            $data['extension'],
        ])));

        $user = User::create([
            'hrId' => $data['hrid'],
            'email' => $data['email'] ?? null,
            'firstname' => $data['firstname'],
            'middlename' => $data['middlename'] ?? null,
            'lastname' => $data['lastname'],
            'extname' => $data['extension'] ?? null,
            'fullname' => $fullname,
            'role' => $data['role'] ?? 'Employee',
            'job_title' => $data['job_title'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'active' => false, // Default to inactive
            'date_created' => now()->toDateString(),
            'password' => bcrypt('password'), // Default password
        ]);

        // Log activity
        ActivityLogService::logCreate('Employee', "{$fullname} (HRID: {$data['hrid']})");

        return response()->json([
            'success' => true,
            'message' => 'Employee created successfully.',
            'employee' => $employee,
        ]);
    }

    public function destroy($employee)
    {
        // Find employee by hrid (route parameter)
        $employee = Employee::where('hrid', $employee)->first();

        if (! $employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found.'], 404);
        }

        $hrid = $employee->hrid;
        $name = trim(implode(' ', array_filter([
            $employee->firstname,
            $employee->middlename,
            $employee->lastname,
        ])));

        // Delete related user if exists
        User::where('hrId', $hrid)->delete();

        // Delete employee
        $employee->delete();

        ActivityLogService::logDelete('Employee', "{$name} (HRID: {$hrid})");

        return response()->json(['success' => true]);
    }
}
