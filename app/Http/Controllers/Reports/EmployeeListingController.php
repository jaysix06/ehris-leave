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
                'jobTitles' => $jobTitles,
                'subjects' => $subjects,
                'gradeLevels' => $gradeLevels,
                'employmentStatuses' => $employmentStatuses,
            ],
            'filters' => $request->only(['school', 'job_title', 'subject', 'grade_level', 'employment_status', 'salary_grade', 'search']),
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

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('firstname', 'like', '%'.$search.'%')
                    ->orWhere('lastname', 'like', '%'.$search.'%')
                    ->orWhere('employee_id', 'like', '%'.$search.'%')
                    ->orWhere('hrid', 'like', '%'.$search.'%');
            });
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
            'filters' => $request->only(['school', 'job_title', 'subject', 'grade_level', 'employment_status', 'salary_grade', 'search']),
        ]);
    }
}
