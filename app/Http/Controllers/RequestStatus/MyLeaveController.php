<?php

namespace App\Http\Controllers\RequestStatus;

use App\Events\LeaveRequestUpdated;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LeaveWorkflowNotificationService;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class MyLeaveController extends Controller
{
    private const LEAVE_TABLE = 'tbl_leave_applications';

    public function __construct(
        private readonly LeaveWorkflowNotificationService $leaveWorkflowNotificationService,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('RequestStatus/MyLeave');
    }

    public function datatables(Request $request): JsonResponse
    {
        if (! Schema::hasTable(self::LEAVE_TABLE)) {
            return response()->json([
                'draw' => (int) $request->get('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $hrid = $this->resolveHrid($request->user());

        if ($hrid <= 0) {
            return response()->json([
                'draw' => (int) $request->get('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $draw = (int) $request->get('draw', 1);
        $start = (int) $request->get('start', 0);
        $length = (int) $request->get('length', 10);
        $showAll = $length === -1;

        $baseQuery = DB::table(self::LEAVE_TABLE)->where('employee_hrid', $hrid);
        $totalRecords = (clone $baseQuery)->count();

        $query = clone $baseQuery;

        $searchValue = $request->input('search.value');
        if ($searchValue && trim($searchValue) !== '') {
            $term = trim($searchValue);
            $query->where(function ($q) use ($term) {
                $q->where('leave_type', 'like', "%{$term}%")
                    ->orWhere('workflow_status', 'like', "%{$term}%")
                    ->orWhere('reason_text', 'like', "%{$term}%");
            });
        }

        $filteredRecords = (clone $query)->count();

        $columns = [
            'leave_type',
            'leave_start_date',
            'leave_days',
            'date_applied',
            'workflow_status',
            'leave_application_id',
        ];

        $orderColumnIndex = (int) $request->input('order.0.column', 3);
        $orderDir = $request->input('order.0.dir', 'desc');
        $orderColumn = $columns[$orderColumnIndex] ?? 'date_applied';

        $query->orderBy($orderColumn, $orderDir);

        if ($showAll) {
            $length = $filteredRecords;
            $start = 0;
        } else {
            $length = max($length, 1);
        }

        $rows = $query->skip($start)->take($length)->get();

        $data = $rows->map(function (object $row) {
            $start = Carbon::parse($row->leave_start_date);
            $end = Carbon::parse($row->leave_end_date);
            $duration = $start->format('d M Y').' – '.$end->format('d M Y');

            return [
                'leave_type' => $row->leave_type,
                'duration' => $duration,
                'leave_days' => $row->leave_days,
                'date_applied' => Carbon::parse($row->date_applied)->format('d M Y'),
                'workflow_status' => $row->workflow_status,
                'leave_application_id' => $row->leave_application_id,
                '_raw' => [
                    'id' => $row->leave_application_id,
                    'leave_type' => $row->leave_type,
                    'workflow_status' => $row->workflow_status,
                    'reason_text' => $row->reason_text,
                ],
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    public function cancel(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        if (! Schema::hasTable(self::LEAVE_TABLE)) {
            return back()->withErrors(['cancel' => 'Leave table not available.']);
        }

        $hrid = $this->resolveHrid($request->user());

        if ($hrid <= 0) {
            return back()->withErrors(['cancel' => 'Unable to identify employee.']);
        }

        $leave = DB::table(self::LEAVE_TABLE)
            ->where('leave_application_id', $id)
            ->where('employee_hrid', $hrid)
            ->first();

        if (! $leave) {
            return back()->withErrors(['cancel' => 'Leave application not found.']);
        }

        if (! in_array($leave->workflow_status, ['pending_rm', 'pending_hr'], true)) {
            return back()->withErrors(['cancel' => 'Only pending leave requests can be cancelled.']);
        }

        DB::table(self::LEAVE_TABLE)
            ->where('leave_application_id', $id)
            ->delete();

        // Add activity log for leave request cancellation
        $leaveType = $leave->leave_type ?? 'Unknown';
        $leaveDays = $leave->leave_days ?? 0;
        $startDate = $leave->leave_start_date ?? 'Unknown';
        ActivityLogService::logDelete(
            'Leave Request',
            "Cancelled Leave Request #{$id} - {$leaveType} ({$leaveDays} days, starting {$startDate})",
            $request->user()?->userId ?? null,
        );

        $this->leaveWorkflowNotificationService->notifyCancelled(
            isset($leave->leave_application_id) ? (int) $leave->leave_application_id : $id,
            isset($leave->rm_assignee_hrid) ? (int) $leave->rm_assignee_hrid : null,
            trim((string) ($request->user()?->name ?? '')) ?: null,
        );

        LeaveRequestUpdated::dispatch(
            isset($leave->leave_application_id) ? (int) $leave->leave_application_id : $id,
            isset($leave->employee_hrid) ? (int) $leave->employee_hrid : null,
            isset($leave->rm_assignee_hrid) ? (int) $leave->rm_assignee_hrid : null,
            'cancelled',
            'cancelled',
            trim((string) ($request->user()?->name ?? '')) ?: null,
            'employee',
            $hrid > 0 ? $hrid : null,
        );

        return back()->with('success', 'Leave request cancelled successfully.');
    }

    private function resolveHrid(mixed $authUser): int
    {
        if ($authUser === null) {
            return 0;
        }

        if (! empty($authUser->hrId)) {
            return (int) $authUser->hrId;
        }

        if (Schema::hasTable('tbl_user')) {
            $profile = User::query()
                ->select('hrId')
                ->where('email', $authUser->email)
                ->first();

            return (int) ($profile?->hrId ?? 0);
        }

        return 0;
    }
}
