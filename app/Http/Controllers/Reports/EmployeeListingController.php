<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class EmployeeListingController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::query();

        // Apply filters
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

        // Get filter options for dropdowns
        $schools = Employee::whereNotNull('office')
            ->distinct()
            ->pluck('office')
            ->sort()
            ->values();

        $jobTitles = Employee::whereNotNull('job_title')
            ->distinct()
            ->pluck('job_title')
            ->sort()
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
            ->limit(12)
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

        // Pagination
        $perPage = $request->get('per_page', 25);
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
}
