<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
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
