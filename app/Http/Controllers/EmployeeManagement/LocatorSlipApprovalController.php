<?php

namespace App\Http\Controllers\EmployeeManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeManagement\DecideLocatorSlipRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class LocatorSlipApprovalController extends Controller
{
    private const LOCATOR_SLIP_TABLE = 'tbl_locator_slips';

    public function index(Request $request): Response
    {
        if (! $this->canAccessQueue($request->user())) {
            $previousUrl = url()->previous();
            $currentUrl = $request->fullUrl();
            $redirectTo = $previousUrl !== '' && $previousUrl !== $currentUrl
                ? $previousUrl
                : route('dashboard');

            return Inertia::render('EmployeeManagement/LocatorSlipApprovals', [
                'accessDenied' => true,
                'deniedMessage' => 'Access denied. Only reporting managers, HR, and admins can view locator slip approvals.',
                'redirectTo' => $redirectTo,
                'canAct' => false,
            ]);
        }

        return Inertia::render('EmployeeManagement/LocatorSlipApprovals', [
            'accessDenied' => false,
            'deniedMessage' => null,
            'redirectTo' => null,
            'canAct' => $this->isReportingManagerApprover($request->user()),
        ]);
    }

    public function datatables(Request $request): JsonResponse
    {
        $draw = (int) $request->get('draw', 1);

        if (
            ! Schema::hasTable(self::LOCATOR_SLIP_TABLE)
            || ! Schema::hasColumn(self::LOCATOR_SLIP_TABLE, 'rm_assignee_hrid')
            || ! Schema::hasColumn(self::LOCATOR_SLIP_TABLE, 'workflow_status')
        ) {
            return response()->json([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        if (! $this->canAccessQueue($request->user())) {
            return response()->json([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Unauthorized.',
            ], 403);
        }

        $authHrid = $this->resolveHrid($request->user());
        $isRm = $this->isReportingManagerApprover($request->user());
        $isAdminOrHr = $this->isAdmin($request->user()) || $this->isHr($request->user());
        $start = max(0, (int) $request->get('start', 0));
        $length = (int) $request->get('length', 10);
        $showAll = $length === -1;

        if (! $showAll) {
            $length = max(1, $length);
        }

        $baseQuery = DB::table(self::LOCATOR_SLIP_TABLE.' as ls')
            ->leftJoin('tbl_emp_official_info as actor', 'actor.hrid', '=', 'ls.rm_acted_by')
            ->leftJoin('tbl_emp_official_info as manager', 'manager.hrid', '=', 'ls.rm_assignee_hrid');

        if (! $isAdminOrHr && $authHrid > 0) {
            $baseQuery->where('ls.rm_assignee_hrid', $authHrid);
        }

        $totalRecords = (clone $baseQuery)->count();
        $query = clone $baseQuery;

        $searchValue = trim((string) $request->input('search.value', ''));
        if ($searchValue !== '') {
            $term = "%{$searchValue}%";
            $query->where(function ($builder) use ($term) {
                $builder->where('ls.control_no', 'like', $term)
                    ->orWhere('ls.employee_name', 'like', $term)
                    ->orWhere('ls.position_designation', 'like', $term)
                    ->orWhere('ls.permanent_station', 'like', $term)
                    ->orWhere('ls.purpose_of_travel', 'like', $term)
                    ->orWhere('ls.destination', 'like', $term)
                    ->orWhere('ls.workflow_status', 'like', $term)
                    ->orWhereRaw("TRIM(CONCAT_WS(' ', manager.firstname, manager.middlename, manager.lastname, manager.extension)) like ?", [$term]);
            });
        }

        $filteredRecords = (clone $query)->count();
        $orderColumns = [
            'ls.control_no',
            'ls.employee_name',
            'ls.permanent_station',
            'ls.purpose_of_travel',
            'ls.travel_date',
            'ls.date_of_filing',
            'ls.workflow_status',
            'ls.rm_assignee_hrid',
            'ls.rm_remarks',
            'ls.id',
        ];
        $orderColumnIndex = (int) $request->input('order.0.column', 5);
        $orderDir = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $orderColumn = $orderColumns[$orderColumnIndex] ?? 'ls.date_of_filing';

        $query
            ->select([
                'ls.id',
                'ls.control_no',
                'ls.employee_name',
                'ls.position_designation',
                'ls.permanent_station',
                'ls.purpose_of_travel',
                'ls.travel_type',
                'ls.travel_date',
                'ls.time_out',
                'ls.time_in',
                'ls.destination',
                'ls.date_of_filing',
                'ls.workflow_status',
                'ls.status',
                'ls.rm_assignee_hrid',
                'ls.rm_status',
                'ls.rm_remarks',
                'ls.rm_acted_by',
                DB::raw("TRIM(CONCAT_WS(' ', actor.firstname, actor.middlename, actor.lastname, actor.extension)) as acted_by_name"),
                DB::raw("TRIM(CONCAT_WS(' ', manager.firstname, manager.middlename, manager.lastname, manager.extension)) as manager_name"),
            ])
            ->orderBy($orderColumn, $orderDir)
            ->orderByDesc('ls.date_of_filing')
            ->orderByDesc('ls.id');

        if (! $showAll) {
            $query->skip($start)->take($length);
        }

        $rows = $query->get();
        $data = $rows->map(function (object $row) use ($authHrid, $isRm): array {
            $managerName = trim((string) ($row->manager_name ?? ''));
            $canActOnRow = $isRm
                && $authHrid > 0
                && (int) ($row->rm_assignee_hrid ?? 0) === $authHrid
                && strtolower((string) ($row->workflow_status ?? '')) === 'pending_rm';

            return [
                'id' => (int) ($row->id ?? 0),
                'control_no' => (string) ($row->control_no ?? ''),
                'employee_name' => (string) ($row->employee_name ?? 'Unknown employee'),
                'position_designation' => (string) ($row->position_designation ?? ''),
                'permanent_station' => (string) ($row->permanent_station ?? ''),
                'purpose_of_travel' => (string) ($row->purpose_of_travel ?? ''),
                'travel_schedule' => $this->formatTravelSchedule(
                    $row->travel_date ?? null,
                    $row->time_out ?? null,
                    $row->time_in ?? null
                ),
                'date_of_filing' => $this->formatDate($row->date_of_filing ?? null) ?? 'N/A',
                'workflow_status' => (string) ($row->workflow_status ?? ''),
                'reporting_manager' => $managerName !== ''
                    ? $managerName
                    : ((int) ($row->rm_assignee_hrid ?? 0) > 0 ? 'HRID '.(int) $row->rm_assignee_hrid : 'Unassigned'),
                'remarks' => (string) ($row->rm_remarks ?? ''),
                'can_act' => $canActOnRow,
                '_raw' => [
                    'workflow_status' => (string) ($row->workflow_status ?? ''),
                    'status' => (string) ($row->status ?? ''),
                    'destination' => (string) ($row->destination ?? ''),
                    'travel_type' => (string) ($row->travel_type ?? ''),
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

    public function decide(DecideLocatorSlipRequest $request, int $id): \Illuminate\Http\RedirectResponse
    {
        if (! Schema::hasTable(self::LOCATOR_SLIP_TABLE)) {
            return back()->withErrors(['decision' => 'Locator slip table not found.']);
        }

        if (! $this->canAccessQueue($request->user())) {
            return back()->withErrors(['decision' => 'You are not authorized to access locator slip approvals.']);
        }

        if (! $this->isReportingManagerApprover($request->user())) {
            return back()->withErrors(['decision' => 'Only the assigned reporting manager can approve or disapprove locator slips.']);
        }

        $slip = DB::table(self::LOCATOR_SLIP_TABLE)
            ->where('id', $id)
            ->first();

        if ($slip === null) {
            return back()->withErrors(['decision' => 'Locator slip request not found.']);
        }

        $authHrid = $this->resolveHrid($request->user());
        if ((int) ($slip->rm_assignee_hrid ?? 0) !== $authHrid) {
            return back()->withErrors(['decision' => 'You are not assigned to this locator slip request.']);
        }

        if (strtolower((string) ($slip->workflow_status ?? '')) !== 'pending_rm') {
            return back()->withErrors(['decision' => 'This locator slip is no longer pending reporting manager approval.']);
        }

        $decision = (string) $request->validated('decision');
        $remarks = $request->filled('remarks')
            ? trim((string) $request->validated('remarks'))
            : null;
        $approved = $decision === 'approve';

        DB::table(self::LOCATOR_SLIP_TABLE)
            ->where('id', $id)
            ->update([
                'workflow_status' => $approved ? 'approved' : 'disapproved',
                'rm_status' => $approved ? 'approved' : 'disapproved',
                'rm_acted_by' => $authHrid > 0 ? $authHrid : null,
                'rm_action_at' => now(),
                'rm_remarks' => $remarks,
                'remarks' => $remarks,
                'status' => $approved ? 'Approved' : 'Disapproved',
                'updated_at' => now(),
            ]);

        return back()->with('status', $approved
            ? 'Locator slip approved successfully.'
            : 'Locator slip disapproved successfully.');
    }

    private function canAccessQueue(mixed $authUser): bool
    {
        return $this->isAdmin($authUser)
            || $this->isHr($authUser)
            || $this->isReportingManagerApprover($authUser);
    }

    private function isReportingManagerApprover(mixed $authUser): bool
    {
        $role = $this->normalizeRole((string) ($authUser?->role ?? ''));
        if (str_contains($role, 'reporting manager')) {
            return true;
        }

        $hrid = $this->resolveHrid($authUser);

        return $hrid > 0
            && Schema::hasTable('tbl_reporting_manager')
            && DB::table('tbl_reporting_manager')
                ->whereRaw('CAST(manager_name AS UNSIGNED) = ?', [$hrid])
                ->exists();
    }

    private function isHr(mixed $authUser): bool
    {
        return str_contains($this->normalizeRole((string) ($authUser?->role ?? '')), 'hr');
    }

    private function isAdmin(mixed $authUser): bool
    {
        return str_contains($this->normalizeRole((string) ($authUser?->role ?? '')), 'admin');
    }

    private function resolveHrid(mixed $authUser): int
    {
        if ($authUser === null) {
            return 0;
        }

        if (! empty($authUser->hrId)) {
            return (int) $authUser->hrId;
        }

        $userId = (int) ($authUser->userId ?? $authUser->id ?? $authUser?->getKey() ?? 0);
        if (Schema::hasTable('tbl_user')) {
            if ($userId > 0) {
                $profile = User::query()
                    ->select('hrId')
                    ->where('userId', $userId)
                    ->first();

                $resolvedFromUserId = (int) ($profile?->hrId ?? 0);
                if ($resolvedFromUserId > 0) {
                    return $resolvedFromUserId;
                }
            }

            if (! empty($authUser->email)) {
                $profile = User::query()
                    ->select('hrId')
                    ->where('email', (string) $authUser->email)
                    ->first();

                $resolvedFromEmail = (int) ($profile?->hrId ?? 0);
                if ($resolvedFromEmail > 0) {
                    return $resolvedFromEmail;
                }
            }
        }

        if (Schema::hasTable('tbl_emp_official_info') && Schema::hasColumn('tbl_emp_official_info', 'email') && ! empty($authUser->email)) {
            $officialHrid = (int) DB::table('tbl_emp_official_info')
                ->where('email', (string) $authUser->email)
                ->value('hrid');

            if ($officialHrid > 0) {
                return $officialHrid;
            }
        }

        return $userId > 0 ? $userId : 0;
    }

    private function normalizeRole(string $role): string
    {
        $normalized = strtolower(trim($role));
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized) ?? '';
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? '';

        return trim($normalized);
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

    private function formatTravelSchedule(mixed $travelDate, mixed $timeOut, mixed $timeIn): string
    {
        $parts = array_filter([
            $this->formatDate($travelDate),
            $this->formatTimeLabel('Out', $timeOut),
            $this->formatTimeLabel('In', $timeIn),
        ]);

        return $parts !== [] ? implode(' | ', $parts) : 'N/A';
    }

    private function formatTimeLabel(string $label, mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        try {
            return $label.': '.Carbon::parse((string) $value)->format('h:i A');
        } catch (\Throwable) {
            return null;
        }
    }
}
