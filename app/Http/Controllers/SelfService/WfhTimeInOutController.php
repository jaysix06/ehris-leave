<?php

namespace App\Http\Controllers\SelfService;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\SelfServiceTask;
use App\Models\User;
use App\Services\WfhTaskListPdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
                    'due_date' => $t->due_date->format('Y-m-d'),
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
                    'due_date' => $t->due_date->format('Y-m-d'),
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
     * Export tasks as PDF. Layout and structure refer to app/Models/generate_pdf.php.
     */
    public function exportPdf(Request $request): StreamedResponse
    {
        $request->validate(['type' => ['required', 'string', 'in:open,completed']]);
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $type = $request->input('type');
        $openStatuses = ['Not Started', 'In Progress', 'On Hold', 'open'];
        $completedStatuses = ['Complete', 'completed'];

        $query = SelfServiceTask::where('user_id', $user->getKey())
            ->whereIn('status', $type === 'completed' ? $completedStatuses : $openStatuses);

        if ($type === 'completed') {
            $query->orderByDesc('updated_at');
        } else {
            $query->orderBy('due_date');
        }

        $tasks = $query->get();

        $subtitle = $type === 'open'
            ? 'Tasks (Open) - as of '.now()->format('F j, Y')
            : 'Tasks (Completed) - as of '.now()->format('F j, Y');

        $htmlTable = '<table><thead><tr>';
        $htmlTable .= '<th>Title</th><th>Description</th><th>Priority</th><th>Due Date</th><th>Due Date End</th><th>Status</th>';
        $htmlTable .= '</tr></thead><tbody>';
        foreach ($tasks as $t) {
            $htmlTable .= '<tr>';
            $htmlTable .= '<td>'.e($t->title).'</td>';
            $htmlTable .= '<td>'.e($t->description ?? '').'</td>';
            $htmlTable .= '<td>'.e($t->priority).'</td>';
            $htmlTable .= '<td>'.e($t->due_date?->format('Y-m-d') ?? '').'</td>';
            $htmlTable .= '<td>'.e($t->due_date_end?->format('Y-m-d') ?? '').'</td>';
            $htmlTable .= '<td>'.e($t->status).'</td>';
            $htmlTable .= '</tr>';
        }
        $htmlTable .= '</tbody></table>';

        // Table styles refer to app/Models/generate_pdf.php (lines 144–147)
        $styles = '<style>
            table { border-collapse: collapse; width: 100%; font-family: Calibri; font-size: 9pt; }
            td { border: 1px solid #040303; padding: 2px; }
            th { font-weight: bold; border: 1px solid #050505; background-color: #f2f2f2; text-align: center; }
        </style>';
        $finalHtml = $styles.$htmlTable;

        $pdf = new WfhTaskListPdf('L', 'in', 'A4', true, 'UTF-8', false, $subtitle);
        $pdf->SetTitle('Tasklist Report');
        $pdf->SetKeywords('DEPED Tasklist Report');
        $pdf->SetMargins(0.6, 3.0, 0.6);
        $pdf->SetFooterMargin(defined('PDF_MARGIN_FOOTER') ? PDF_MARGIN_FOOTER + 1 : 1.5);
        $pdf->SetAutoPageBreak(true, 1.5);
        $pdf->AddPage();
        $pdf->writeHTML($finalHtml, true, false, true, false, '');

        $filename = 'tasklist_report_'.$type.'_'.now()->format('Y-m-d').'.pdf';

        $pdfString = $pdf->Output('', 'S');

        return response()->streamDownload(
            function () use ($pdfString): void {
                echo $pdfString;
            },
            $filename,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]
        );
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
}
