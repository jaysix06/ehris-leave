<?php

namespace App\Http\Controllers\EmployeeManagement;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class LeaveRequestsController extends Controller
{
    private const LEAVE_TABLE = 'tbl_leave_applications';

    public function index(Request $request): Response|RedirectResponse
    {
        $hrid = $this->resolveHrid($request->user());

        if (! $this->isReportingManager($hrid)) {
            return redirect()->back()->withErrors([
                'leave_requests' => 'Access denied. Only reporting managers can view leave requests.',
            ]);
        }

        return Inertia::render('EmployeeManagement/LeaveRequests');
    }

    public function datatables(Request $request): JsonResponse
    {
        $draw = (int) $request->get('draw', 1);

        if (! Schema::hasTable(self::LEAVE_TABLE) || ! Schema::hasColumn(self::LEAVE_TABLE, 'rm_assignee_hrid')) {
            return response()->json([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $authHrid = $this->resolveHrid($request->user());
        if (! $this->isReportingManager($authHrid)) {
            return response()->json([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Unauthorized.',
            ], 403);
        }

        $start = max(0, (int) $request->get('start', 0));
        $length = (int) $request->get('length', 10);
        $showAll = $length === -1;
        if (! $showAll) {
            $length = max(1, $length);
        }

        $baseQuery = DB::table(self::LEAVE_TABLE.' as lr')
            ->leftJoin('tbl_emp_official_info as e', 'e.hrid', '=', 'lr.employee_hrid')
            ->leftJoin('tbl_emp_official_info as actor', 'actor.hrid', '=', 'lr.rm_acted_by')
            ->where('lr.rm_assignee_hrid', $authHrid);

        $totalRecords = (clone $baseQuery)->count();
        $query = clone $baseQuery;

        $searchValue = trim((string) $request->input('search.value', ''));
        if ($searchValue !== '') {
            $term = "%{$searchValue}%";
            $query->where(function ($q) use ($term) {
                $q->where('lr.leave_type', 'like', $term)
                    ->orWhere('lr.workflow_status', 'like', $term)
                    ->orWhere('lr.reason_text', 'like', $term)
                    ->orWhere('lr.employee_hrid', 'like', $term)
                    ->orWhereRaw("TRIM(CONCAT_WS(' ', e.firstname, e.middlename, e.lastname, e.extension)) like ?", [$term]);
            });
        }

        $filteredRecords = (clone $query)->count();

        $orderColumns = [
            'lr.leave_application_id',
            'e.lastname',
            'lr.leave_type',
            'lr.leave_start_date',
            'lr.leave_days',
            'lr.date_applied',
            'lr.workflow_status',
            'lr.rm_acted_by',
            'lr.rm_remarks',
            'lr.leave_application_id',
        ];
        $orderColumnIndex = (int) $request->input('order.0.column', 5);
        $orderDir = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $orderColumn = $orderColumns[$orderColumnIndex] ?? 'lr.date_applied';

        $query
            ->select([
                'lr.leave_application_id',
                'lr.employee_hrid',
                'lr.leave_type',
                'lr.leave_start_date',
                'lr.leave_end_date',
                'lr.leave_days',
                'lr.workflow_status',
                'lr.date_applied',
                'lr.reason_text',
                'lr.rm_status',
                'lr.rm_remarks',
                'lr.rm_acted_by',
                'lr.rm_action_at',
                'e.firstname',
                'e.middlename',
                'e.lastname',
                'e.extension',
                DB::raw("TRIM(CONCAT_WS(' ', actor.firstname, actor.middlename, actor.lastname, actor.extension)) as acted_by_name"),
            ])
            ->orderBy($orderColumn, $orderDir)
            ->orderByDesc('lr.date_applied');

        if (! $showAll) {
            $query->skip($start)->take($length);
        }

        $rows = $query->get();
        $data = $rows->map(function (object $row) {
            $employeeName = trim((string) implode(' ', array_filter([
                trim((string) ($row->firstname ?? '')),
                trim((string) ($row->middlename ?? '')),
                trim((string) ($row->lastname ?? '')),
                trim((string) ($row->extension ?? '')),
            ], fn ($part) => $part !== '')));

            return [
                'id' => (int) ($row->leave_application_id ?? 0),
                'employee_name' => $employeeName !== '' ? $employeeName : 'Unknown employee',
                'employee_hrid' => (int) ($row->employee_hrid ?? 0),
                'leave_type' => (string) ($row->leave_type ?? ''),
                'date_range' => $this->formatDateRange($row->leave_start_date ?? null, $row->leave_end_date ?? null),
                'leave_days' => (int) ($row->leave_days ?? 0),
                'date_applied' => $this->formatDate($row->date_applied ?? null) ?? 'N/A',
                'workflow_status' => (string) ($row->workflow_status ?? ''),
                'acted_by' => trim((string) ($row->acted_by_name ?? '')) !== ''
                    ? (string) $row->acted_by_name
                    : ((int) ($row->rm_acted_by ?? 0) > 0 ? 'HRID '.(int) $row->rm_acted_by : 'Pending'),
                'remarks' => (string) ($row->rm_remarks ?? ''),
                '_raw' => [
                    'id' => (int) ($row->leave_application_id ?? 0),
                    'workflow_status' => (string) ($row->workflow_status ?? ''),
                    'rm_status' => (string) ($row->rm_status ?? ''),
                ],
            ];
        })->values()->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
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

    private function formatDate(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->format('d M Y');
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatDateRange(mixed $startDate, mixed $endDate): string
    {
        $start = $this->formatDate($startDate);
        $end = $this->formatDate($endDate);

        if ($start === null && $end === null) {
            return 'N/A';
        }

        if ($start !== null && $end !== null) {
            return $start.' - '.$end;
        }

        return (string) ($start ?? $end);
    }

    private function isReportingManager(int $hrid): bool
    {
        return $hrid > 0
            && Schema::hasTable('tbl_reporting_manager')
            && DB::table('tbl_reporting_manager')
                ->whereRaw('CAST(manager_name AS UNSIGNED) = ?', [$hrid])
                ->exists();
    }
}
