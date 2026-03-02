<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeListingController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->buildEmployeeQuery($request);
        $perPage = (int) $request->get('per_page', 10);

        // Partial reload: only fetch employees so only the table updates (no charts/summary refetch)
        $partialData = $request->header('X-Inertia-Partial-Data');
        if ($partialData) {
            $wanted = array_map('trim', explode(',', $partialData));
            if (in_array('employees', $wanted)) {
                $employees = $query->paginate($perPage)->withQueryString();

                return Inertia::render('Reports/EmployeeListing', [
                    'employees' => $employees,
                ]);
            }
        }

        // Get filter options for dropdowns
        $schools = Employee::whereNotNull('office')
            ->distinct()
            ->pluck('office')
            ->sort()
            ->values();

        // Schools/Offices grouped by District (Business Code + Business Name from tbl_business / tbl_department)
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
            ->sort()
            ->values();

        $gradeLevels = Employee::whereNotNull('grade_level')
            ->distinct()
            ->pluck('grade_level')
            ->sort()
            ->values();

        $employmentStatuses = Employee::whereNotNull('employ_status')
            ->distinct()
            ->pluck('employ_status')
            ->sort()
            ->values();

        // Calculate summary stats before pagination
        $total = $query->count();
        $permanentQuery = clone $query;
        $permanent = $permanentQuery->where('employ_status', 'Permanent')->count();

        // Calculate average leave balance from the same filtered query
        $avgLeaveBalanceQuery = clone $query;
        $avgLeaveBalance = $avgLeaveBalanceQuery->avg('leave_balance') ?? 0;

        // Chart data: distributions from the same filtered query (before pagination)
        // Use explicit select() and groupBy(column) to satisfy MySQL ONLY_FULL_GROUP_BY

        // Employment Status: Get all records, split for legend (top 4) and others
        $allEmploymentStatus = (clone $query)
            ->select(DB::raw('COALESCE(employ_status, \'(Blank)\') as label'), DB::raw('count(*) as count'))
            ->groupBy('employ_status')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => ['label' => $row->label ?? '(Blank)', 'count' => (int) $row->count])
            ->values();

        $employmentStatusTop = $allEmploymentStatus->take(5)->all();
        $employmentStatusOthers = $allEmploymentStatus->skip(5)->all();

        $jobTitleDistribution = (clone $query)
            ->select(DB::raw('COALESCE(job_title, \'(Blank)\') as label'), DB::raw('count(*) as count'))
            ->groupBy('job_title')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn ($row) => ['label' => $row->label ?? '(Blank)', 'count' => (int) $row->count])
            ->values()
            ->all();

        // School/Office: Get all records, split for legend (top 4) and others
        $allSchoolDistribution = (clone $query)
            ->select(DB::raw('COALESCE(office, \'(Blank)\') as label'), DB::raw('count(*) as count'))
            ->groupBy('office')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => ['label' => (string) ($row->label ?? '(Blank)'), 'count' => (int) $row->count])
            ->values();

        $schoolTop = $allSchoolDistribution->take(5)->all();
        $schoolOthers = $allSchoolDistribution->skip(5)->all();

        // Pagination (full load)
        $employees = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Reports/EmployeeListing', [
            'employees' => $employees,
            'summaryStats' => [
                'total' => $total,
                'permanent' => $permanent,
                'avgLeaveBalance' => number_format($avgLeaveBalance, 1),
            ],
            'chartData' => [
                'employmentStatus' => [
                    'chart' => $allEmploymentStatus->all(),
                    'legend' => $employmentStatusTop,
                    'others' => $employmentStatusOthers,
                ],
                'jobTitle' => $jobTitleDistribution,
                'school' => [
                    'chart' => $allSchoolDistribution->all(),
                    'legend' => $schoolTop,
                    'others' => $schoolOthers,
                ],
            ],
            'filterOptions' => [
                'schools' => $schools,
                'schoolsGrouped' => $schoolsGrouped,
                'jobTitles' => $jobTitles,
                'subjects' => $subjects,
                'gradeLevels' => $gradeLevels,
                'employmentStatuses' => $employmentStatuses,
            ],
            'filters' => $request->only(['school', 'district', 'job_title', 'subject', 'grade_level', 'employment_status', 'salary_grade', 'search']),
        ]);
    }

    /**
     * Build a query with filters applied (reusable for exports and API)
     */
    private function buildEmployeeQuery(Request $request)
    {
        $query = Employee::query();

        // Apply filters (same as index method)
        if ($request->filled('school')) {
            $query->where('office', $request->school);
        } elseif ($request->filled('district')) {
            // Filter by district: employees whose business_id matches the district code (e.g. 92001 = District 1)
            // tbl_emp_official_info.business_id matches tbl_business.BusinessUnitId / tbl_department.business_id
            $districtCode = $request->district;
            $query->where('business_id', (string) $districtCode);
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

        // Handle search parameter - could be string or array from DataTables
        $searchValue = $request->input('search');
        if ($searchValue) {
            // If it's an array (from DataTables), get the value
            if (is_array($searchValue)) {
                $searchValue = $searchValue['value'] ?? null;
            }
            // Only apply if we have a valid string
            if ($searchValue && is_string($searchValue) && trim($searchValue) !== '') {
                $search = trim($searchValue);
                $query->where(function ($q) use ($search) {
                    $q->where('firstname', 'like', '%'.$search.'%')
                        ->orWhere('lastname', 'like', '%'.$search.'%')
                        ->orWhere('employee_id', 'like', '%'.$search.'%')
                        ->orWhere('hrid', 'like', '%'.$search.'%');
                });
            }
        }

        return $query;
    }

    /**
     * API endpoint for JSON pagination (no Inertia loading)
     */
    public function api(Request $request)
    {
        $query = $this->buildEmployeeQuery($request);
        $perPage = (int) $request->get('per_page', 10);
        $employees = $query->paginate($perPage)->withQueryString();

        return response()->json($employees);
    }

    /**
     * API endpoint for summary stats (filtered)
     */
    public function summaryStats(Request $request)
    {
        $query = $this->buildEmployeeQuery($request);

        $total = $query->count();
        $permanentQuery = clone $query;
        $permanent = $permanentQuery->where('employ_status', 'Permanent')->count();

        $avgLeaveBalanceQuery = clone $query;
        $avgLeaveBalance = $avgLeaveBalanceQuery->avg('leave_balance') ?? 0;

        return response()->json([
            'total' => $total,
            'permanent' => $permanent,
            'avgLeaveBalance' => number_format($avgLeaveBalance, 1),
        ]);
    }

    /**
     * DataTables server-side processing endpoint
     */
    public function datatables(Request $request)
    {
        try {
            // Build base query with filters (but not search yet)
            $baseQuery = $this->buildEmployeeQuery($request);

            // DataTables parameters
            $draw = (int) $request->get('draw', 1);
            $start = (int) $request->get('start', 0);
            $length = (int) $request->get('length', 10);

            // Check if "All" option was selected (-1 means show all records)
            $showAll = $length === -1;

            // Get total count before any filters or search (for recordsTotal)
            $totalRecords = Employee::count();

            // Handle DataTables search parameter (global search)
            // DataTables sends search as: search[value] = "search term"
            $searchValue = $request->input('search.value');

            $query = clone $baseQuery; // Clone to avoid modifying base query

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

            // Get filtered count (after applying filters and search)
            $filteredRecords = $query->count();

            // Handle "All" option - set length to filtered count and reset start
            if ($showAll) {
                $length = $filteredRecords;
                $start = 0; // Reset start when showing all
            } else {
                $length = $length > 0 ? $length : 10;
            }

            // Handle DataTables ordering
            $orderColumnIndex = (int) ($request->input('order.0.column', 0));
            $orderDir = $request->input('order.0.dir', 'asc');

            // Map column index to database column
            // Column order (must match frontend DataTable columns):
            // hrid, employee_id, name, job_title,
            // subject_taught, grade_level, office, station_code, salary_grade, salary_step,
            // employ_status, leave_balance
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

            // Handle special case for 'name' column (needs to sort by firstname, lastname)
            if ($orderColumn === 'name') {
                $query->orderBy('firstname', $orderDir)
                    ->orderBy('lastname', $orderDir);
            } elseif ($orderColumn === 'leave_balance') {
                $query->orderBy('leave_balance', $orderDir);
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
                    'hrid' => $employee->hrid ?? '',
                    'employee_id' => $employee->employee_id ?? '',
                    'name' => $fullName,
                    'job_title' => $employee->job_title ?? '',
                    'employ_status' => $employee->employ_status ?? '',
                    'leave_balance' => $employee->leave_balance ?? 0,
                    'subject_taught' => $employee->subject_taught ?? '',
                    'grade_level' => $employee->grade_level ?? '',
                    'office' => $employee->office ?? '',
                    'station_code' => $employee->station_code ?? '',
                    'salary_grade' => $employee->salary_grade ?? '',
                    'salary_step' => $employee->salary_step ?? '',
                    // Include full employee object for custom rendering
                    '_raw' => $employee,
                ];
            });

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('DataTables Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            // Return error response in DataTables format
            return response()->json([
                'draw' => (int) $request->get('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'An error occurred while processing your request. Please try again.',
            ], 500);
        }
    }

    /**
     * Export employee listing as CSV
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $query = $this->buildEmployeeQuery($request);
        $employees = $query->get();

        $filename = 'employee-listing-'.now()->format('Y-m-d-His').'.csv';

        return Response::streamDownload(function () use ($employees) {
            $handle = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($handle, [
                'HRID',
                'Employee ID',
                'Name',
                'Job Title',
                'Subject',
                'Grade Level',
                'School/Office',
                'Station Code',
                'Salary Grade',
                'Salary Step',
                'Employment Status',
                'Leave Balance',
            ]);

            // Add data rows
            foreach ($employees as $employee) {
                $fullName = trim(implode(' ', array_filter([
                    $employee->firstname,
                    $employee->middlename,
                    $employee->lastname,
                    $employee->extension,
                ])));

                fputcsv($handle, [
                    $employee->hrid ?? '',
                    $employee->employee_id ?? '',
                    $fullName,
                    $employee->job_title ?? '',
                    $employee->subject_taught ?? '',
                    $employee->grade_level ?? '',
                    $employee->office ?? '',
                    $employee->station_code ?? '',
                    $employee->salary_grade ?? '',
                    $employee->salary_step ?? '',
                    $employee->employ_status ?? '',
                    $employee->leave_balance ?? 0,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export employee listing as Excel
     */
    public function exportExcel(Request $request)
    {
        $query = $this->buildEmployeeQuery($request);
        $employees = $query->get();

        $filename = 'employee-listing-'.now()->format('Y-m-d-His').'.xlsx';

        $data = $employees->map(function ($employee) {
            $fullName = trim(implode(' ', array_filter([
                $employee->firstname,
                $employee->middlename,
                $employee->lastname,
                $employee->extension,
            ])));

            return [
                'HRID' => $employee->hrid ?? '',
                'Employee ID' => $employee->employee_id ?? '',
                'Name' => $fullName,
                'Job Title' => $employee->job_title ?? '',
                'Subject' => $employee->subject_taught ?? '',
                'Grade Level' => $employee->grade_level ?? '',
                'School/Office' => $employee->office ?? '',
                'Station Code' => $employee->station_code ?? '',
                'Salary Grade' => $employee->salary_grade ?? '',
                'Salary Step' => $employee->salary_step ?? '',
                'Employment Status' => $employee->employ_status ?? '',
                'Leave Balance' => $employee->leave_balance ?? 0,
            ];
        })->toArray();

        return Excel::download(
            new class($data) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithColumnWidths, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithStyles
            {
                public function __construct(private array $data) {}

                public function array(): array
                {
                    return $this->data;
                }

                public function headings(): array
                {
                    return [
                        'HRID',
                        'Employee ID',
                        'Name',
                        'Job Title',
                        'Subject',
                        'Grade Level',
                        'School/Office',
                        'Station Code',
                        'Salary Grade',
                        'Salary Step',
                        'Employment Status',
                        'Leave Balance',
                    ];
                }

                public function columnWidths(): array
                {
                    return [
                        'A' => 12, // HRID
                        'B' => 15, // Employee ID
                        'C' => 30, // Name
                        'D' => 25, // Job Title
                        'E' => 20, // Subject
                        'F' => 12, // Grade Level
                        'G' => 30, // School/Office
                        'H' => 15, // Station Code
                        'I' => 12, // Salary Grade
                        'J' => 12, // Salary Step
                        'K' => 20, // Employment Status
                        'L' => 15, // Leave Balance
                    ];
                }

                public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): array
                {
                    return [
                        1 => ['font' => ['bold' => true]],
                    ];
                }
            },
            $filename
        );
    }

    /**
     * Export employee listing for printing
     */
    public function exportPrint(Request $request)
    {
        $query = $this->buildEmployeeQuery($request);
        $employees = $query->get();

        return Inertia::render('Reports/EmployeeListingPrint', [
            'employees' => $employees,
            'filters' => $request->only(['school', 'district', 'job_title', 'subject', 'grade_level', 'employment_status', 'salary_grade', 'search']),
        ]);
    }
}
