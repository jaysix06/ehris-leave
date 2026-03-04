<?php

namespace App\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use App\Models\JobTitle;
use App\Models\MonthlySalary;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JobTitleMonthlySalaryController extends Controller
{
    public function index()
    {
        return Inertia::render('Utilities/JobTitleMonthlySalary');
    }

    public function jobTitlesDatatables(Request $request)
    {
        try {
            $draw = (int) $request->get('draw', 1);
            $start = (int) $request->get('start', 0);
            $length = (int) $request->get('length', 10);
            $showAll = $length === -1;

            $totalRecords = JobTitle::count();

            $query = JobTitle::query();

            $searchValue = $request->input('search.value');
            if ($searchValue && trim($searchValue) !== '') {
                $searchTerm = trim($searchValue);
                $query->where('job_title', 'like', '%'.$searchTerm.'%');
            }

            $filteredRecords = $query->count();

            if ($showAll) {
                $length = $filteredRecords;
                $start = 0;
            } else {
                $length = $length > 0 ? $length : 10;
            }

            // Handle ordering - default to alphabetical by job_title
            $orderColumnIndex = (int) ($request->input('order.0.column', 1)); // Default to column 1 (job_title)
            $orderDir = $request->input('order.0.dir', 'asc');
            $columns = ['id', 'job_title'];
            $orderColumn = $columns[$orderColumnIndex] ?? 'job_title';

            // If no order is specified in request, default to job_title ascending
            if (!$request->has('order.0.column')) {
                $orderColumn = 'job_title';
                $orderDir = 'asc';
            }

            $query->getQuery()->orders = [];
            $query->orderBy($orderColumn, $orderDir);

            $jobTitles = $query->skip($start)->take($length)->get();

            $data = $jobTitles->map(function ($jobTitle, $index) use ($start) {
                return [
                    'id' => $jobTitle->id,
                    'row_number' => $start + $index + 1,
                    'job_title' => $jobTitle->job_title ?? '',
                    'job_shorten' => $jobTitle->job_shorten ?? null,
                    '_raw' => $jobTitle,
                ];
            });

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Log::error('Job Title DataTables Error: '.$e->getMessage());

            return response()->json([
                'draw' => (int) $request->get('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'An error occurred while processing your request.',
            ], 500);
        }
    }

    public function monthlySalariesDatatables(Request $request)
    {
        try {
            $draw = (int) $request->get('draw', 1);
            $start = (int) $request->get('start', 0);
            $length = (int) $request->get('length', 10);
            $showAll = $length === -1;

            $totalRecords = MonthlySalary::count();

            $query = MonthlySalary::query()
                ->orderBy('salary_grade', 'desc')
                ->orderBy('salary_step', 'asc');

            $searchValue = $request->input('search.value');
            if ($searchValue && trim($searchValue) !== '') {
                $searchTerm = trim($searchValue);
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('salary_grade', 'like', '%'.$searchTerm.'%')
                        ->orWhere('salary_step', 'like', '%'.$searchTerm.'%')
                        ->orWhere('salary_amount', 'like', '%'.$searchTerm.'%');
                });
            }

            $filteredRecords = $query->count();

            if ($showAll) {
                $length = $filteredRecords;
                $start = 0;
            } else {
                $length = $length > 0 ? $length : 10;
            }

            $orderColumnIndex = (int) ($request->input('order.0.column', 0));
            $orderDir = $request->input('order.0.dir', 'asc');
            $columns = ['id', 'salary_grade', 'salary_step', 'salary_amount'];
            $orderColumn = $columns[$orderColumnIndex] ?? 'salary_grade';

            $query->getQuery()->orders = [];
            $query->orderBy($orderColumn, $orderDir);

            $salaries = $query->skip($start)->take($length)->get();

            $data = $salaries->map(function ($salary, $index) use ($start) {
                return [
                    'id' => $salary->id,
                    'row_number' => $start + $index + 1,
                    'salary_grade' => $salary->salary_grade ?? '',
                    'salary_step' => $salary->salary_step ?? '',
                    'salary_amount' => $salary->salary_amount ? 'P '.number_format($salary->salary_amount, 2) : 'P 0.00',
                    '_raw' => $salary,
                ];
            });

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Log::error('Monthly Salary DataTables Error: '.$e->getMessage());

            return response()->json([
                'draw' => (int) $request->get('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'An error occurred while processing your request.',
            ], 500);
        }
    }

    public function storeJobTitle(Request $request)
    {
        try {
            $validated = $request->validate([
                'job_title' => 'required|string|max:50|unique:tbl_job_title,job_title',
                'job_shorten' => 'required|string|max:50',
            ]);

            // Check if combination of job_title and job_shorten already exists
            $existing = JobTitle::where('job_title', $validated['job_title'])
                ->where('job_shorten', $validated['job_shorten'])
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'A job title with this name and shorten already exists.',
                    'errors' => [
                        'job_title' => ['A job title with this name and shorten already exists.'],
                        'job_shorten' => ['A job title with this name and shorten already exists.'],
                    ],
                ], 422);
            }

            $jobTitle = JobTitle::create([
                'job_title' => $validated['job_title'],
                'job_shorten' => $validated['job_shorten'],
            ]);

            ActivityLogService::logCreate('Job Title', $validated['job_title']);

            return response()->json([
                'success' => true,
                'message' => 'Job title created successfully.',
                'data' => $jobTitle,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error creating job title: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the job title.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function updateJobTitle(Request $request, $id)
    {
        $request->validate([
            'job_title' => 'required|string|max:50|unique:tbl_job_title,job_title,'.$id.',id',
            'job_shorten' => 'required|string|max:50',
        ]);

        // Check if combination of job_title and job_shorten already exists (excluding current record)
        $existing = JobTitle::where('job_title', $request->job_title)
            ->where('job_shorten', $request->job_shorten)
            ->where('id', '!=', $id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'A job title with this name and shorten already exists.',
                'errors' => [
                    'job_title' => ['A job title with this name and shorten already exists.'],
                    'job_shorten' => ['A job title with this name and shorten already exists.'],
                ],
            ], 422);
        }

        $jobTitle = JobTitle::findOrFail($id);
        $oldTitle = $jobTitle->job_title;
        $jobTitle->update([
            'job_title' => $request->job_title,
            'job_shorten' => $request->job_shorten,
        ]);

        ActivityLogService::logUpdate('Job Title', "Changed from '{$oldTitle}' to '{$request->job_title}'");

        return response()->json([
            'success' => true,
            'message' => 'Job title updated successfully.',
            'data' => $jobTitle,
        ]);
    }

    public function destroyJobTitle($id)
    {
        $jobTitle = JobTitle::findOrFail($id);
        $title = $jobTitle->job_title;
        $jobTitle->delete();

        ActivityLogService::logDelete('Job Title', $title);

        return response()->json([
            'success' => true,
            'message' => 'Job title deleted successfully.',
        ]);
    }

    public function storeMonthlySalary(Request $request)
    {
        $request->validate([
            'salary_grade' => 'required|integer|min:1|max:33',
            'salary_step' => 'required|integer|min:1|max:8',
            'salary_amount' => 'required|numeric|min:0',
        ]);

        // Check if combination of salary_grade, salary_step, and salary_amount already exists
        $existing = MonthlySalary::where('salary_grade', $request->salary_grade)
            ->where('salary_step', $request->salary_step)
            ->where('salary_amount', $request->salary_amount)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'A monthly salary with this grade, step, and amount already exists.',
                'errors' => [
                    'salary_grade' => ['A monthly salary with this grade, step, and amount already exists.'],
                    'salary_step' => ['A monthly salary with this grade, step, and amount already exists.'],
                    'salary_amount' => ['A monthly salary with this grade, step, and amount already exists.'],
                ],
            ], 422);
        }

        $monthlySalary = MonthlySalary::create([
            'salary_grade' => $request->salary_grade,
            'salary_step' => $request->salary_step,
            'salary_amount' => $request->salary_amount,
        ]);

        ActivityLogService::logCreate('Monthly Salary', "SG {$request->salary_grade}, Step {$request->salary_step}, P ".number_format($request->salary_amount, 2));

        return response()->json([
            'success' => true,
            'message' => 'Monthly salary created successfully.',
            'data' => $monthlySalary,
        ]);
    }

    public function updateMonthlySalary(Request $request, $id)
    {
        $request->validate([
            'salary_grade' => 'required|integer|min:1|max:33',
            'salary_step' => 'required|integer|min:1|max:8',
            'salary_amount' => 'required|numeric|min:0',
        ]);

        // Check if combination of salary_grade, salary_step, and salary_amount already exists (excluding current record)
        $existing = MonthlySalary::where('salary_grade', $request->salary_grade)
            ->where('salary_step', $request->salary_step)
            ->where('salary_amount', $request->salary_amount)
            ->where('id', '!=', $id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'A monthly salary with this grade, step, and amount already exists.',
                'errors' => [
                    'salary_grade' => ['A monthly salary with this grade, step, and amount already exists.'],
                    'salary_step' => ['A monthly salary with this grade, step, and amount already exists.'],
                    'salary_amount' => ['A monthly salary with this grade, step, and amount already exists.'],
                ],
            ], 422);
        }

        $monthlySalary = MonthlySalary::findOrFail($id);
        $oldData = "SG {$monthlySalary->salary_grade}, Step {$monthlySalary->salary_step}, P ".number_format($monthlySalary->salary_amount, 2);
        $monthlySalary->update([
            'salary_grade' => $request->salary_grade,
            'salary_step' => $request->salary_step,
            'salary_amount' => $request->salary_amount,
        ]);
        $newData = "SG {$request->salary_grade}, Step {$request->salary_step}, P ".number_format($request->salary_amount, 2);

        ActivityLogService::logUpdate('Monthly Salary', "Changed from {$oldData} to {$newData}");

        return response()->json([
            'success' => true,
            'message' => 'Monthly salary updated successfully.',
            'data' => $monthlySalary,
        ]);
    }

    public function destroyMonthlySalary($id)
    {
        $monthlySalary = MonthlySalary::findOrFail($id);
        $data = "SG {$monthlySalary->salary_grade}, Step {$monthlySalary->salary_step}, P ".number_format($monthlySalary->salary_amount, 2);
        $monthlySalary->delete();

        ActivityLogService::logDelete('Monthly Salary', $data);

        return response()->json([
            'success' => true,
            'message' => 'Monthly salary deleted successfully.',
        ]);
    }
}
