<?php

namespace App\Http\Controllers\EmployeeManagement;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\SelfServiceTask;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeTasksController extends Controller
{
    public function index(Request $request): Response
    {
        $selectedDate = Carbon::parse($request->validate([
            'date' => ['nullable', 'date'],
        ])['date'] ?? now()->toDateString())->toDateString();

        if (! $this->canManageEmployeeTasks($request->user())) {
            return Inertia::render('EmployeeManagement/EmployeeTasks', [
                'accessDenied' => true,
                'deniedMessage' => 'Access denied. Only HR and admin users can view employee tasks.',
                'selectedDate' => $selectedDate,
                'employees' => [],
            ]);
        }

        $tasksByUserId = $this->tasksByUserId($selectedDate);
        $attendanceByHrid = $this->attendanceByHrid($selectedDate);
        $employees = $this->employeeCards($tasksByUserId, $attendanceByHrid);

        return Inertia::render('EmployeeManagement/EmployeeTasks', [
            'accessDenied' => false,
            'deniedMessage' => null,
            'selectedDate' => $selectedDate,
            'employees' => $employees->values()->all(),
        ]);
    }

    /**
     * @return Collection<int, array<int, array<string, mixed>>>
     */
    private function tasksByUserId(string $selectedDate): Collection
    {
        if (! Schema::hasTable('tbl_self_service_tasks')) {
            return collect();
        }

        return SelfServiceTask::query()
            ->select([
                'id',
                'user_id',
                'title',
                'description',
                'priority',
                'status',
                'due_date',
                'due_date_end',
                'accomplishment_report',
            ])
            ->where(function ($query) use ($selectedDate) {
                $query
                    ->where(function ($singleDayQuery) use ($selectedDate) {
                        $singleDayQuery
                            ->whereNull('due_date_end')
                            ->whereDate('due_date', $selectedDate);
                    })
                    ->orWhere(function ($rangeQuery) use ($selectedDate) {
                        $rangeQuery
                            ->whereNotNull('due_date_end')
                            ->whereDate('due_date', '<=', $selectedDate)
                            ->whereDate('due_date_end', '>=', $selectedDate);
                    });
            })
            ->orderBy('user_id')
            ->orderBy('due_date')
            ->orderBy('title')
            ->get()
            ->map(function (SelfServiceTask $task): array {
                return [
                    'user_id' => (int) $task->user_id,
                    'id' => (int) $task->id,
                    'title' => (string) $task->title,
                    'description' => (string) $task->description,
                    'priority' => (string) $task->priority,
                    'status' => (string) $task->status,
                    'due_date' => $task->due_date?->format('Y-m-d'),
                    'due_date_end' => $task->due_date_end?->format('Y-m-d'),
                    'accomplishment_report' => filled($task->accomplishment_report)
                        ? (string) $task->accomplishment_report
                        : null,
                ];
            })
            ->groupBy('user_id');
    }

    /**
     * @return Collection<int, array{clock_in: string|null, clock_out: string|null}>
     */
    private function attendanceByHrid(string $selectedDate): Collection
    {
        if (! Schema::hasTable('tbl_attendance')) {
            return collect();
        }

        return Attendance::query()
            ->select(['hrid', 'time_in', 'time_out'])
            ->whereDate('time_in', $selectedDate)
            ->orderBy('time_in')
            ->get()
            ->groupBy('hrid')
            ->map(function (Collection $records): array {
                /** @var Attendance|null $firstRecord */
                $firstRecord = $records->sortBy('time_in')->first();
                /** @var Attendance|null $lastRecord */
                $lastRecord = $records
                    ->filter(fn (Attendance $attendance): bool => $attendance->time_out !== null)
                    ->sortByDesc('time_out')
                    ->first();

                return [
                    'clock_in' => $firstRecord?->time_in?->format('h:i A'),
                    'clock_out' => $lastRecord?->time_out?->format('h:i A'),
                ];
            });
    }

    /**
     * @param  Collection<int, array<int, array<string, mixed>>>  $tasksByUserId
     * @param  Collection<int, array{clock_in: string|null, clock_out: string|null}>  $attendanceByHrid
     * @return Collection<int, array<string, mixed>>
     */
    private function employeeCards(Collection $tasksByUserId, Collection $attendanceByHrid): Collection
    {
        $taskUserIds = $tasksByUserId->keys()
            ->map(fn (mixed $value): int => (int) $value)
            ->filter(fn (int $value): bool => $value > 0)
            ->values();
        $attendanceHrids = $attendanceByHrid->keys()
            ->map(fn (mixed $value): int => (int) $value)
            ->filter(fn (int $value): bool => $value > 0)
            ->values();

        if ($taskUserIds->isEmpty() && $attendanceHrids->isEmpty()) {
            return collect();
        }

        $employeeTableAvailable = Schema::hasTable('tbl_emp_official_info');
        $query = User::query()
            ->select([
                'tbl_user.userId as user_id',
                'tbl_user.hrId as hrid',
                'tbl_user.avatar',
                'tbl_user.role',
                'tbl_user.job_title as user_job_title',
                'tbl_user.firstname as user_firstname',
                'tbl_user.middlename as user_middlename',
                'tbl_user.lastname as user_lastname',
                'tbl_user.extname as user_extname',
                'tbl_user.fullname as user_fullname',
            ]);

        if ($employeeTableAvailable) {
            $query
                ->leftJoin('tbl_emp_official_info as employee', 'employee.hrid', '=', 'tbl_user.hrId')
                ->addSelect([
                    'employee.firstname as employee_firstname',
                    'employee.middlename as employee_middlename',
                    'employee.lastname as employee_lastname',
                    'employee.extension as employee_extension',
                    'employee.job_title as employee_job_title',
                ]);
        }

        $users = $query
            ->where(function ($userQuery) use ($taskUserIds, $attendanceHrids) {
                if ($taskUserIds->isNotEmpty()) {
                    $userQuery->whereIn('tbl_user.userId', $taskUserIds->all());
                }

                if ($attendanceHrids->isNotEmpty()) {
                    $method = $taskUserIds->isNotEmpty() ? 'orWhereIn' : 'whereIn';
                    $userQuery->{$method}('tbl_user.hrId', $attendanceHrids->all());
                }
            })
            ->orderBy('tbl_user.lastname')
            ->orderBy('tbl_user.firstname')
            ->get()
            ->unique(fn (User $user): int => (int) ($user->getAttribute('user_id') ?? $user->getKey() ?? 0))
            ->values();

        return $users->map(function (User $user) use ($tasksByUserId, $attendanceByHrid): array {
            $userId = (int) ($user->getAttribute('user_id') ?? $user->getKey() ?? 0);
            $hrid = (int) ($user->getAttribute('hrid') ?? $user->hrId ?? 0);
            $tasks = $tasksByUserId->get($userId, []);
            $attendance = $attendanceByHrid->get($hrid, [
                'clock_in' => null,
                'clock_out' => null,
            ]);

            return [
                'user_id' => $userId,
                'hrid' => $hrid,
                'name' => $this->employeeDisplayName($user),
                'role' => trim((string) ($user->role ?? '')) !== '' ? (string) $user->role : 'Employee',
                'job_title' => $this->employeeJobTitle($user),
                'avatar' => $user->avatar,
                'clock_in' => $attendance['clock_in'],
                'clock_out' => $attendance['clock_out'],
                'tasks' => $tasks instanceof Collection ? $tasks->values()->all() : array_values($tasks),
            ];
        })->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }

    private function employeeDisplayName(User $user): string
    {
        $employeeName = $this->combineNameParts([
            $user->getAttribute('employee_firstname'),
            $user->getAttribute('employee_middlename'),
            $user->getAttribute('employee_lastname'),
            $user->getAttribute('employee_extension'),
        ]);

        if ($employeeName !== '') {
            return $employeeName;
        }

        $fullName = trim((string) ($user->getAttribute('user_fullname') ?? $user->fullname ?? ''));
        if ($fullName !== '') {
            return $fullName;
        }

        $userName = $this->combineNameParts([
            $user->getAttribute('user_firstname'),
            $user->getAttribute('user_middlename'),
            $user->getAttribute('user_lastname'),
            $user->getAttribute('user_extname'),
        ]);

        return $userName !== '' ? $userName : (string) ($user->email ?? 'Unknown employee');
    }

    private function employeeJobTitle(User $user): string
    {
        $employeeJobTitle = trim((string) ($user->getAttribute('employee_job_title') ?? ''));
        if ($employeeJobTitle !== '') {
            return $employeeJobTitle;
        }

        $userJobTitle = trim((string) ($user->getAttribute('user_job_title') ?? $user->job_title ?? ''));

        return $userJobTitle !== '' ? $userJobTitle : 'No job title assigned';
    }

    /**
     * @param  array<int, mixed>  $parts
     */
    private function combineNameParts(array $parts): string
    {
        return trim((string) implode(' ', array_filter(
            array_map(fn (mixed $value): string => trim((string) $value), $parts),
            fn (string $value): bool => $value !== ''
        )));
    }

    public function exportPdf(Request $request): HttpResponse
    {
        if (! $this->canManageEmployeeTasks($request->user())) {
            abort(403, 'Access denied. Only HR and admin users can export employee tasks.');
        }

        $request->validate([
            'user_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
        ]);

        $userId = (int) $request->input('user_id');
        $selectedDate = Carbon::parse($request->input('date'))->toDateString();

        $user = User::find($userId);
        if (! $user) {
            abort(404, 'Employee not found.');
        }

        // Get tasks for the selected date (same logic as index method)
        $tasks = SelfServiceTask::query()
            ->where('user_id', $userId)
            ->where(function ($query) use ($selectedDate) {
                $query
                    ->where(function ($singleDayQuery) use ($selectedDate) {
                        $singleDayQuery
                            ->whereNull('due_date_end')
                            ->whereDate('due_date', $selectedDate);
                    })
                    ->orWhere(function ($rangeQuery) use ($selectedDate) {
                        $rangeQuery
                            ->whereNotNull('due_date_end')
                            ->whereDate('due_date', '<=', $selectedDate)
                            ->whereDate('due_date_end', '>=', $selectedDate);
                    });
            })
            ->orderBy('due_date')
            ->orderBy('title')
            ->get();

        if ($tasks->isEmpty()) {
            abort(404, 'No tasks found for this employee on the selected date.');
        }

        // Use the same date for both from and to (single day export)
        $dateFrom = Carbon::parse($selectedDate)->startOfDay();
        $dateTo = Carbon::parse($selectedDate)->endOfDay();

        $subtitle = '('.$dateFrom->format('F d, Y').')';
        [$employeeName, $station] = $this->getEmployeeNameAndStationForPdf($user);

        $tasksForView = $tasks->map(function ($t) {
            $start = $t->due_date?->format('m/d/Y') ?? '';
            $end = ($t->due_date_end && $t->due_date_end != $t->due_date)
                ? $t->due_date_end->format('m/d/Y')
                : $start;
            $status = (string) ($t->status ?? '');
            $isComplete = in_array($status, ['Complete', 'completed'], true);
            $isOnHold = in_array($status, ['On Hold', 'on hold'], true);
            $accomplishment = '';
            if ($isComplete) {
                $accomplishment = (string) ($t->accomplishment_report ?? '');
            } elseif ($isOnHold) {
                $accomplishment = 'On Hold';
            } else {
                $accomplishment = 'In Progress';
            }

            return [
                'targeted_task' => $t->description ?? '',
                'accomplishment' => $accomplishment,
                'date_range' => $start.'-'.$end,
                'priority' => $t->priority ?? '',
            ];
        })->all();

        [$headerImageDataUri, $footerImageDataUri] = $this->getWfhPdfTemplateImageDataUris();

        $html = $this->buildWfhPdfHtml(
            $headerImageDataUri,
            $footerImageDataUri,
            $subtitle,
            $employeeName,
            $station,
            $tasksForView
        );

        $filename = 'employee_task_report_'.$employeeName.'_'.$selectedDate.'.pdf';
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);

        $dompdfFontPaths = $this->ensureDompdfFontDirectoriesExist();

        $pdf = Pdf::setOption([
            'font_dir' => $dompdfFontPaths['font_dir'],
            'font_cache' => $dompdfFontPaths['font_cache'],
        ])->loadHTML($html)->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }

    private function canManageEmployeeTasks(mixed $authUser): bool
    {
        $role = strtolower(trim((string) ($authUser?->role ?? '')));

        if ($role === '' && $authUser !== null && ! empty($authUser->email)) {
            $role = strtolower(trim((string) User::query()
                ->where('email', (string) $authUser->email)
                ->value('role')));
        }

        return str_contains($role, 'admin') || str_contains($role, 'hr');
    }

    /**
     * Get employee name and station for PDF (reused from WfhTimeInOutController logic).
     *
     * @return array{0: string, 1: string} [displayName, station]
     */
    private function getEmployeeNameAndStationForPdf(User $user): array
    {
        $name = $this->employeeDisplayName($user);
        $station = '';

        if (Schema::hasTable('tbl_emp_official_info')) {
            $hrid = (int) ($user->hrId ?? 0);
            if ($hrid > 0) {
                $emp = Employee::query()
                    ->where('hrid', $hrid)
                    ->first();
                if ($emp && Schema::hasColumn('tbl_emp_official_info', 'station')) {
                    $station = (string) ($emp->station ?? '');
                }
            }
        }

        return [$name, $station];
    }

    /**
     * Get WFH PDF header/footer images as data URIs (reused from WfhTimeInOutController).
     *
     * @return array{0: string|null, 1: string|null} [headerImageDataUri, footerImageDataUri]
     */
    private function getWfhPdfTemplateImageDataUris(): array
    {
        $base = rtrim(public_path(), '/\\');
        $headerPath = $base.'/Accomplishment Report Templates/header.jpg';
        if (! is_file($headerPath)) {
            $headerPath = $base.\DIRECTORY_SEPARATOR.'Accomplishment Report Templates'.\DIRECTORY_SEPARATOR.'header.jpg';
        }
        if (! is_file($headerPath)) {
            $headerPath = $base.'/assets/img/header.png';
        }
        $footerPath = $base.'/Accomplishment Report Templates/footer.jpg';
        if (! is_file($footerPath)) {
            $footerPath = $base.\DIRECTORY_SEPARATOR.'Accomplishment Report Templates'.\DIRECTORY_SEPARATOR.'footer.jpg';
        }
        if (! is_file($footerPath)) {
            $footerPath = $base.'/assets/img/footer.png';
        }

        $headerDataUri = null;
        if ($headerPath !== '' && is_file($headerPath)) {
            $content = @file_get_contents($headerPath);
            if ($content !== false) {
                $mime = (strtolower(pathinfo($headerPath, PATHINFO_EXTENSION)) === 'png') ? 'image/png' : 'image/jpeg';
                $headerDataUri = 'data:'.$mime.';base64,'.base64_encode($content);
            }
        }

        $footerDataUri = null;
        if ($footerPath !== '' && is_file($footerPath)) {
            $content = @file_get_contents($footerPath);
            if ($content !== false) {
                $mime = (strtolower(pathinfo($footerPath, PATHINFO_EXTENSION)) === 'png') ? 'image/png' : 'image/jpeg';
                $footerDataUri = 'data:'.$mime.';base64,'.base64_encode($content);
            }
        }

        return [$headerDataUri, $footerDataUri];
    }

    /**
     * Build WFH PDF HTML by calling WfhTimeInOutController method via reflection.
     *
     * @param  array<int, array{targeted_task: string, accomplishment: string, date_range: string, priority: string}>  $tasks
     */
    private function buildWfhPdfHtml(
        ?string $headerImageDataUri,
        ?string $footerImageDataUri,
        string $subtitle,
        string $employeeName,
        string $station,
        array $tasks
    ): string {
        $wfhController = app(\App\Http\Controllers\SelfService\WfhTimeInOutController::class);
        $reflection = new \ReflectionClass($wfhController);
        $method = $reflection->getMethod('buildWfhPdfHtml');
        $method->setAccessible(true);

        return $method->invoke($wfhController, $headerImageDataUri, $footerImageDataUri, $subtitle, $employeeName, $station, $tasks);
    }

    /**
     * Ensure DomPDF font directories exist by calling WfhTimeInOutController method.
     *
     * @return array{font_dir: string, font_cache: string}
     */
    private function ensureDompdfFontDirectoriesExist(): array
    {
        $wfhController = app(\App\Http\Controllers\SelfService\WfhTimeInOutController::class);
        $reflection = new \ReflectionClass($wfhController);
        $method = $reflection->getMethod('ensureDompdfFontDirectoriesExist');
        $method->setAccessible(true);

        return $method->invoke($wfhController);
    }
}
