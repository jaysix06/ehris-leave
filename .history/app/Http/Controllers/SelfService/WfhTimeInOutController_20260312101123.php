<?php

namespace App\Http\Controllers\SelfService;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\SelfServiceTask;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class WfhTimeInOutController extends Controller
{
    public function index(Request $request): Response
    {
        $isClockedIn = false;
        $hoursWorkedThisWeek = '00:00:00';

        if (Schema::hasTable('tbl_attendance')) {
            $hrid = $this->resolveHrid($request->user());
            if ($hrid > 0) {
                $openRecord = Attendance::where('hrid', $hrid)
                    ->whereNull('time_out')
                    ->orderByDesc('time_in')
                    ->first();
                $isClockedIn = $openRecord !== null;
                $hoursWorkedThisWeek = $this->getHoursWorkedThisWeek($hrid);
            }
        }

        $openTasks = [];
        $completedTasks = [];
        if (Schema::hasTable('tbl_self_service_tasks') && $request->user()) {
            $userId = $request->user()->getKey();
            $openStatuses = ['Not Started', 'In Progress', 'On Hold', 'open'];
            $openTasks = SelfServiceTask::where('user_id', $userId)
                ->whereIn('status', $openStatuses)
                ->orderBy('due_date')
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'title' => $t->title,
                    'description' => $t->description,
                    'priority' => $t->priority,
                    'due_date' => $t->due_date?->format('Y-m-d') ?? '',
                    'due_date_end' => $t->due_date_end?->format('Y-m-d') ?? null,
                    'add_to_calendar' => $t->add_to_calendar,
                    'status' => $t->status,
                    'accomplishment_report' => $t->accomplishment_report,
                ]);
            $completedTasks = SelfServiceTask::where('user_id', $userId)
                ->whereIn('status', ['Complete', 'completed'])
                ->orderByDesc('updated_at')
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'title' => $t->title,
                    'description' => $t->description,
                    'priority' => $t->priority,
                    'due_date' => $t->due_date?->format('Y-m-d') ?? '',
                    'due_date_end' => $t->due_date_end?->format('Y-m-d') ?? null,
                    'add_to_calendar' => $t->add_to_calendar,
                    'status' => $t->status,
                    'accomplishment_report' => $t->accomplishment_report,
                ]);
        }

        return Inertia::render('SelfService/WorkfromHome', [
            'attendance' => [
                'isClockedIn' => $isClockedIn,
                'hoursWorkedThisWeek' => $hoursWorkedThisWeek,
            ],
            'openTasks' => $openTasks,
            'completedTasks' => $completedTasks,
            'successMessage' => $request->session()->get('successMessage'),
            'errorMessage' => $request->session()->get('errorMessage'),
        ]);
    }

    public function storeTask(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => ['required', 'string', 'max:50'],
            'description' => ['required', 'string'],
            'priority' => ['required', 'string', 'in:Low,Medium,High'],
            'due_date' => ['required', 'date'],
            'due_date_end' => ['nullable', 'date', 'after_or_equal:due_date'],
        ]);

        if (! $request->user()) {
            return redirect()->route('self-service.wfh-time-in-out')->with('errorMessage', 'You must be logged in to create a task.');
        }

        SelfServiceTask::create([
            'user_id' => $request->user()->getKey(),
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'priority' => $request->input('priority'),
            'due_date' => $request->input('due_date'),
            'due_date_end' => $request->input('due_date_end'),
            'add_to_calendar' => true,
            'status' => 'Not Started',
        ]);

        return redirect()->route('self-service.wfh-time-in-out')->with('successMessage', 'Task created successfully.');
    }

    public function updateTaskStatus(Request $request, SelfServiceTask $task): RedirectResponse
    {
        if (! $request->user() || $task->user_id !== $request->user()->getKey()) {
            return redirect()->route('self-service.wfh-time-in-out')->with('errorMessage', 'Unauthorized.');
        }

        $request->validate([
            'status' => ['required', 'string', 'in:Not Started,In Progress,On Hold,Complete'],
            'accomplishment_report' => ['nullable', 'string', 'max:5000'],
        ]);

        $data = ['status' => $request->input('status')];

        if ($request->input('status') === 'Complete' && $request->filled('accomplishment_report')) {
            $data['accomplishment_report'] = $request->input('accomplishment_report');
        }

        $task->update($data);

        return redirect()->route('self-service.wfh-time-in-out');
    }

    public function updateTask(Request $request, SelfServiceTask $task): RedirectResponse
    {
        if (! $request->user() || $task->user_id !== $request->user()->getKey()) {
            return redirect()->route('self-service.wfh-time-in-out')->with('errorMessage', 'Unauthorized.');
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:50'],
            'description' => ['required', 'string'],
            'priority' => ['required', 'string', 'in:Low,Medium,High'],
            'due_date' => ['required', 'date'],
            'due_date_end' => ['nullable', 'date', 'after_or_equal:due_date'],
        ]);

        $task->update($data);

        return redirect()->route('self-service.wfh-time-in-out')->with('successMessage', 'Task updated.');
    }

    /**
     * Export tasks as PDF (DomPDF, HTML built in PHP — no Blade).
     * Accepts date_from and date_to (Y-m-d). Exports all tasks (open + completed) whose due date range overlaps the selected range.
     */
    public function exportPdf(Request $request): HttpResponse
    {
        $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
        ]);
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $dateFrom = Carbon::parse($request->input('date_from'))->startOfDay();
        $dateTo = Carbon::parse($request->input('date_to'))->endOfDay();

        $tasks = SelfServiceTask::where('user_id', $user->getKey())
            ->where('due_date', '<=', $dateTo)
            ->where(function ($q) use ($dateFrom) {
                $q->whereNotNull('due_date_end')->where('due_date_end', '>=', $dateFrom)
                    ->orWhere(function ($q2) use ($dateFrom) {
                        $q2->whereNull('due_date_end')->where('due_date', '>=', $dateFrom);
                    });
            })
            ->orderBy('due_date')
            ->get();

        $subtitle = '('.$dateFrom->format('F d, Y').'-'.$dateTo->format('F d, Y').')';
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

        $filename = 'tasklist_report_'.$dateFrom->format('Y-m-d').'_'.$dateTo->format('Y-m-d').'.pdf';

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }

    /**
     * Build full HTML for WFH Accomplishment Report (no Blade).
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
        $h = fn (string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $fontFaceCss = $this->buildWfhPdfFontFaceCss();
        $headerImg = ($headerImageDataUri !== null && $headerImageDataUri !== '')
            ? '<img src="'.$h($headerImageDataUri).'" alt="Header" class="header-image">'
            : '';
        $footerImg = ($footerImageDataUri !== null && $footerImageDataUri !== '')
            ? '<img src="'.$h($footerImageDataUri).'" alt="Footer" class="footer-image">'
            : '';

        $rows = '';
        foreach ($tasks as $task) {
            $rows .= '<tr>';
            $rows .= '<td class="col-task">'.$h($task['targeted_task']).'</td>';
            $rows .= '<td class="col-accomplishment">'.$h($task['accomplishment']).'</td>';
            $rows .= '<td class="col-date">'.$h($task['date_range']).'</td>';
            $rows .= '<td class="col-priority">'.$h($task['priority']).'</td>';
            $rows .= '<td class="col-station">'.$h($station).'</td>';
            $rows .= '</tr>';
        }
        if ($rows === '') {
            $rows = '<tr><td colspan="5" style="text-align: center;">No tasks in this report.</td></tr>';
        }

        $year = date('Y');
        $subtitleEscaped = $h($subtitle);
        $employeeNameEscaped = $h(mb_strtoupper($employeeName, 'UTF-8'));
        $yearEscaped = $h($year);

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Work From Home Individual Accomplishment Report</title>
<style>
@page { margin: 2.05in 0.35in 0.95in 0.35in; }
{$fontFaceCss}
body {
    font-family: "WFH Bookman", "Bookman Old Style", "DejaVu Serif", serif;
    font-size: 11pt;
    margin: 0;
    padding: 0;
    color: #000;
}
.page-border {
    position: fixed;
    top: -1.65in;
    left: -0.45in;
    right: -0.45in;
    bottom: -0.60in;
    border: 5px solid #000;
    box-sizing: border-box;
    z-index: 0;
}
.page-header {
    position: fixed;
    top: -1.72in;
    left: 0;
    right: 0;
    height: auto;
    z-index: 1;
    text-align: center;
}
.header-image {
    width: auto;
    height: auto;
    max-width: 100%;
    max-height: 1.53in;
    margin: 0 auto 0.06in;
    display: block;
}
.report-title {
    font-size: 14pt;
    font-weight: bold;
    text-align: center;
    margin: 0;
}
.report-date {
    font-size: 11pt;
    text-align: center;
    margin: 0.03in 0 0.06in;
}
.report-title-line {
    border: none;
    border-top: 1px solid #000;
    margin: 0 0 0.08in;
}
.employee-name {
    font-weight: 700;
    margin: 0;
    font-size: 11pt;
    text-transform: uppercase;
}
.page-footer {
    position: fixed;
    left: 0;
    right: 0;
    bottom: -0.62in;
    height: auto;
    z-index: 1;
    text-align: center;
}
.footer-image {
    width: auto;
    height: auto;
    max-width: 100%;
    max-height: 0.99in;
    display: block;
    margin: 0 auto;
}
.footer-text {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0.02in;
    font-size: 8pt;
    font-style: italic;
    color: #666;
    text-align: center;
}
.page-content {
    position: relative;
    z-index: 2;
    margin-top: 0.18in;
}
table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 11pt;
    font-family: "WFH Bookman", "Bookman Old Style", "DejaVu Serif", serif;
}
thead { display: table-header-group; }
tfoot { display: table-footer-group; }
tr { page-break-inside: avoid; page-break-after: auto; }
th, td {
    border: 1px solid #000;
    padding: 3px;
    font-size: 11pt;
    word-break: break-word;
}
th {
    font-weight: bold;
    background-color: #f2f2f2;
}
th.col-task, td.col-task { text-align: left; width: 32%; }
th.col-accomplishment, td.col-accomplishment { text-align: left; width: 28%; }
th.col-date, td.col-date { text-align: center; width: 18%; }
th.col-priority, td.col-priority { text-align: center; width: 10%; }
th.col-station, td.col-station { text-align: left; width: 12%; }
</style>
</head>
<body>
<div class="page-border"></div>

<div class="page-header">
{$headerImg}
<h1 class="report-title">WORK FROM HOME INDIVIDUAL ACCOMPLISHMENT REPORT</h1>
<p class="report-date">{$subtitleEscaped}</p>
<hr class="report-title-line">
<p class="employee-name">Name: {$employeeNameEscaped}</p>
</div>

<div class="page-footer">
{$footerImg}
<p class="footer-text">© {$yearEscaped} DepEd</p>
</div>

<div class="page-content">
<table>
<thead><tr>
<th class="col-task">Targeted Task/ Assignments/ Output</th>
<th class="col-accomplishment">Actual Accomplishment/Output</th>
<th class="col-date">Date</th>
<th class="col-priority">Priority</th>
<th class="col-station">Station</th>
</tr></thead>
<tbody>{$rows}</tbody>
</table>
</div>
</body>
</html>
HTML;
    }

    private function buildWfhPdfFontFaceCss(): string
    {
        $fontPath = $this->resolveWfhPdfNameFontPath();
        if ($fontPath === null) {
            return '';
        }

        $fontUri = $this->toFileUri($fontPath);

        return '@font-face {'
            .' font-family: "WFH Bookman";'
            .' src: url("'.$fontUri.'") format("truetype");'
            .' font-weight: normal;'
            .' font-style: normal;'
            .'}';
    }

    private function resolveWfhPdfNameFontPath(): ?string
    {
        $configured = trim((string) config('ehris.wfh_pdf_name_ttf_font', ''));
        if ($configured === '') {
            return null;
        }

        $candidate = $configured;
        if (! $this->isAbsolutePath($candidate)) {
            $candidate = base_path(ltrim($candidate, '/\\'));
        }

        if (! is_file($candidate) || ! is_readable($candidate)) {
            return null;
        }

        return (string) (realpath($candidate) ?: $candidate);
    }

    private function isAbsolutePath(string $path): bool
    {
        if (str_starts_with($path, '/') || str_starts_with($path, '\\\\')) {
            return true;
        }

        return preg_match('/^[A-Za-z]:[\/\\\\]/', $path) === 1;
    }

    private function toFileUri(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        if (str_starts_with($normalized, '/')) {
            return 'file://'.$normalized;
        }

        return 'file:///'.$normalized;
    }

    public function destroyTask(Request $request, SelfServiceTask $task): RedirectResponse
    {
        if (! $request->user() || $task->user_id !== $request->user()->getKey()) {
            return redirect()->route('self-service.wfh-time-in-out')->with('errorMessage', 'Unauthorized.');
        }

        $task->delete();

        return redirect()->route('self-service.wfh-time-in-out')->with('successMessage', 'Task deleted.');
    }

    public function clockIn(Request $request): Response|RedirectResponse
    {
        $attendance = $this->getAttendanceState($request);

        if (! Schema::hasTable('tbl_attendance')) {
            return redirect()->route('self-service.wfh-time-in-out')->with('errorMessage', 'Attendance table is not available.');
        }

        $hrid = $this->resolveHrid($request->user());
        if ($hrid <= 0) {
            return redirect()->route('self-service.wfh-time-in-out')->with('errorMessage', 'Unable to identify employee.');
        }

        $openRecord = Attendance::where('hrid', $hrid)->whereNull('time_out')->orderByDesc('time_in')->first();
        if ($openRecord !== null) {
            return redirect()->route('self-service.wfh-time-in-out')->with('errorMessage', 'You are already clocked in. Clock out first.');
        }

        Attendance::create([
            'hrid' => $hrid,
            'time_in' => now(),
        ]);

        return redirect()->route('self-service.wfh-time-in-out')->with('successMessage', 'Clocked in successfully.');
    }

    public function clockOut(Request $request): Response|RedirectResponse
    {
        $attendance = $this->getAttendanceState($request);

        if (! Schema::hasTable('tbl_attendance')) {
            return redirect()->route('self-service.wfh-time-in-out')->with('errorMessage', 'Attendance table is not available.');
        }

        $hrid = $this->resolveHrid($request->user());
        if ($hrid <= 0) {
            return redirect()->route('self-service.wfh-time-in-out')->with('errorMessage', 'Unable to identify employee.');
        }

        $openRecord = Attendance::where('hrid', $hrid)->whereNull('time_out')->orderByDesc('time_in')->first();
        if ($openRecord === null) {
            return redirect()->route('self-service.wfh-time-in-out')->with('errorMessage', 'You are not clocked in.');
        }

        $openRecord->update(['time_out' => now()]);

        return redirect()->route('self-service.wfh-time-in-out')->with('successMessage', 'Clocked out successfully.');
    }

    private function getAttendanceState(Request $request): array
    {
        $isClockedIn = false;
        $hoursWorkedThisWeek = '00:00:00';

        if (Schema::hasTable('tbl_attendance')) {
            $hrid = $this->resolveHrid($request->user());
            if ($hrid > 0) {
                $openRecord = Attendance::where('hrid', $hrid)
                    ->whereNull('time_out')
                    ->orderByDesc('time_in')
                    ->first();
                $isClockedIn = $openRecord !== null;
                $hoursWorkedThisWeek = $this->getHoursWorkedThisWeek($hrid);
            }
        }

        return [
            'isClockedIn' => $isClockedIn,
            'hoursWorkedThisWeek' => $hoursWorkedThisWeek,
        ];
    }

    private function getHoursWorkedThisWeek(int $hrid): string
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $records = Attendance::where('hrid', $hrid)
            ->whereNotNull('time_in')
            ->whereNotNull('time_out')
            ->where('time_in', '<=', $endOfWeek)
            ->where('time_out', '>=', $startOfWeek)
            ->get();

        $totalSeconds = 0;
        foreach ($records as $record) {
            $effectiveStart = $record->time_in->isBefore($startOfWeek) ? $startOfWeek : $record->time_in;
            $effectiveEnd = $record->time_out->isAfter($endOfWeek) ? $endOfWeek : $record->time_out;
            $totalSeconds += $effectiveStart->diffInSeconds($effectiveEnd);
        }

        $hours = (int) floor($totalSeconds / 3600);
        $minutes = (int) floor(($totalSeconds % 3600) / 60);
        $seconds = (int) ($totalSeconds % 60);

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    private function resolveHrid(mixed $authUser): int
    {
        if ($authUser === null) {
            return 0;
        }

        // 1. Use hrId from the auth user (tbl_user.hrId)
        if (! empty($authUser->hrId) && (int) $authUser->hrId > 0) {
            return (int) $authUser->hrId;
        }

        // 2. Load full user from DB by email and use hrId (in case session model is missing it)
        if (Schema::hasTable('tbl_user')) {
            $profile = User::query()
                ->select('hrId', 'userId')
                ->where('email', $authUser->email ?? '')
                ->first();

            if ($profile && ! empty($profile->hrId) && (int) $profile->hrId > 0) {
                return (int) $profile->hrId;
            }
        }

        // 3. Find employee by email in tbl_emp_official_info (link user to employee)
        if (Schema::hasTable('tbl_emp_official_info') && Schema::hasColumn('tbl_emp_official_info', 'email')) {
            $email = trim((string) ($authUser->email ?? ''));
            if ($email !== '') {
                $employee = Employee::query()
                    ->select('hrid')
                    ->where('email', $email)
                    ->first();
                if ($employee && ! empty($employee->hrid)) {
                    return (int) $employee->hrid;
                }
            }
        }

        // 4. Fallback: use userId so clock in/out still works (admin can link hrId later)
        $userId = $authUser->userId ?? $authUser->id ?? $authUser->getKey();
        if ($userId !== null && (int) $userId > 0) {
            return (int) $userId;
        }

        return 0;
    }

    /**
     * Get WFH PDF header/footer images as data URIs for DomPDF (no temp files, no path issues).
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
     * Build report date range subtitle for PDF header: (September 01, 2025-March 09, 2026).
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\SelfServiceTask>  $tasks
     */
    private function getReportDateRangeSubtitle($tasks): string
    {
        if ($tasks->isEmpty()) {
            return '('.now()->format('F d, Y').')';
        }
        $minDate = $tasks->min(function ($t) {
            return $t->due_date;
        });
        $maxDate = $tasks->max(function ($t) {
            return $t->due_date_end ?? $t->due_date;
        });
        if (! $minDate || ! $maxDate) {
            return '('.now()->format('F d, Y').')';
        }
        $from = $minDate instanceof \Carbon\Carbon ? $minDate : Carbon::parse($minDate);
        $to = $maxDate instanceof \Carbon\Carbon ? $maxDate : Carbon::parse($maxDate);

        return '('.$from->format('F d, Y').'-'.$to->format('F d, Y').')';
    }

    /** @return array{0: string, 1: string} [displayName, station] */
    private function getEmployeeNameAndStationForPdf($user): array
    {
        if (! $user) {
            return ['', ''];
        }
        $hrid = $this->resolveHrid($user);
        if ($hrid > 0) {
            $emp = Employee::query()
                ->where('hrid', $hrid)
                ->first();
            if ($emp) {
                $first = $emp->firstname ?? '';
                $last = $emp->lastname ?? '';
                $middle = $emp->middlename ?? '';
                $name = trim(trim($first).' '.trim($middle).' '.trim($last));
                if ($name !== '') {
                    $station = $emp->office ?? '';

                    return [$name, $station];
                }
            }
        }

        return [$user->name ?? $user->email ?? 'N/A', ''];
    }
}
