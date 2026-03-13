<?php

namespace App\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ActivityLogController extends Controller
{
    public function index()
    {
        return inertia('Utilities/ActivityLog', [
            'filterOptions' => [
                'severities' => $this->distinctActivityLogValues('severity'),
                'eventTypes' => $this->distinctActivityLogValues('event_type'),
                'modules' => $this->distinctActivityLogValues('module'),
            ],
        ]);
    }

    public function datatables(Request $request)
    {
        try {
            $hasSeverity = Schema::hasColumn('activity_log', 'severity');
            $hasEventType = Schema::hasColumn('activity_log', 'event_type');
            $hasTargetUserId = Schema::hasColumn('activity_log', 'target_user_id');
            $hasIpAddress = Schema::hasColumn('activity_log', 'ip_address');
            $hasHttpMethod = Schema::hasColumn('activity_log', 'http_method');
            $hasRouteName = Schema::hasColumn('activity_log', 'route_name');
            $severityFilter = trim((string) $request->get('severity', ''));
            $eventTypeFilter = trim((string) $request->get('event_type', ''));
            $moduleFilter = trim((string) $request->get('module', ''));
            $dateFromFilter = trim((string) $request->get('date_from', ''));
            $dateToFilter = trim((string) $request->get('date_to', ''));

            // DataTables parameters
            $draw = (int) $request->get('draw', 1);
            $start = (int) $request->get('start', 0);
            $length = (int) $request->get('length', 10);

            // Check if "All" option was selected (-1 means show all records)
            $showAll = $length === -1;

            // Get total count before any filters or search
            $totalRecords = ActivityLog::count();

            // Build base query with user join
            $query = ActivityLog::query()
                ->leftJoin('tbl_user as actor', 'activity_log.fk_user_id', '=', 'actor.userId')
                ->when($hasTargetUserId, function ($builder) {
                    $builder->leftJoin('tbl_user as target', 'activity_log.target_user_id', '=', 'target.userId');
                })
                ->select([
                    'activity_log.log_id',
                    'activity_log.fk_user_id',
                    'activity_log.activity',
                    'activity_log.module',
                    'activity_log.created_at',
                    'actor.email as actor_email',
                    'actor.fullname as actor_fullname',
                    'actor.firstname as actor_firstname',
                    'actor.lastname as actor_lastname',
                    ...($hasSeverity ? ['activity_log.severity'] : []),
                    ...($hasEventType ? ['activity_log.event_type'] : []),
                    ...($hasTargetUserId ? [
                        'activity_log.target_user_id',
                        'target.email as target_email',
                        'target.personal_email as target_personal_email',
                        'target.fullname as target_fullname',
                        'target.firstname as target_firstname',
                        'target.lastname as target_lastname',
                    ] : []),
                    ...($hasIpAddress ? ['activity_log.ip_address'] : []),
                    ...($hasHttpMethod ? ['activity_log.http_method'] : []),
                    ...($hasRouteName ? ['activity_log.route_name'] : []),
                ])
                ->orderBy('activity_log.created_at', 'desc'); // Default to newest first

            if ($hasSeverity && $severityFilter !== '') {
                $query->where('activity_log.severity', $severityFilter);
            }

            if ($hasEventType && $eventTypeFilter !== '') {
                $query->where('activity_log.event_type', $eventTypeFilter);
            }

            if ($moduleFilter !== '') {
                $query->where('activity_log.module', $moduleFilter);
            }

            if ($dateFromFilter !== '') {
                $query->whereDate('activity_log.created_at', '>=', $dateFromFilter);
            }

            if ($dateToFilter !== '') {
                $query->whereDate('activity_log.created_at', '<=', $dateToFilter);
            }

            // Handle DataTables search parameter (global search)
            $searchValue = $request->input('search.value');

            if ($searchValue && trim($searchValue) !== '') {
                $searchTerm = trim($searchValue);
                $query->where(function ($q) use ($searchTerm, $hasSeverity, $hasEventType, $hasTargetUserId, $hasIpAddress, $hasRouteName) {
                    $q->where('activity_log.activity', 'like', '%'.$searchTerm.'%')
                        ->orWhere('activity_log.module', 'like', '%'.$searchTerm.'%')
                        ->orWhere('actor.email', 'like', '%'.$searchTerm.'%')
                        ->orWhere('actor.fullname', 'like', '%'.$searchTerm.'%')
                        ->orWhere('actor.firstname', 'like', '%'.$searchTerm.'%')
                        ->orWhere('actor.lastname', 'like', '%'.$searchTerm.'%');

                    if ($hasSeverity) {
                        $q->orWhere('activity_log.severity', 'like', '%'.$searchTerm.'%');
                    }

                    if ($hasEventType) {
                        $q->orWhere('activity_log.event_type', 'like', '%'.$searchTerm.'%');
                    }

                    if ($hasTargetUserId) {
                        $q->orWhere('target.email', 'like', '%'.$searchTerm.'%')
                            ->orWhere('target.personal_email', 'like', '%'.$searchTerm.'%')
                            ->orWhere('target.fullname', 'like', '%'.$searchTerm.'%');
                    }

                    if ($hasIpAddress) {
                        $q->orWhere('activity_log.ip_address', 'like', '%'.$searchTerm.'%');
                    }

                    if ($hasRouteName) {
                        $q->orWhere('activity_log.route_name', 'like', '%'.$searchTerm.'%');
                    }
                });
            }

            // Get filtered count (after applying search)
            $filteredRecords = $query->count();

            // Handle "All" option - set length to filtered count and reset start
            if ($showAll) {
                $length = $filteredRecords;
                $start = 0;
            } else {
                $length = $length > 0 ? $length : 10;
            }

            // Handle DataTables ordering
            // Default to created_at descending (newest first) when no order is specified
            $orderColumnIndex = (int) ($request->input('order.0.column', 0));
            $orderDir = $request->input('order.0.dir', 'desc'); // Default to desc for newest first

            // Map column index to database column
            // Column order: created_at, severity, event_type, actor, target, activity, module, request
            $columns = [
                'created_at',
                'severity',
                'event_type',
                'activity',
                'module',
                'route_name',
            ];
            $orderColumn = $columns[$orderColumnIndex] ?? 'created_at';

            // If no order is specified in request, default to created_at descending
            if (! $request->has('order.0.column')) {
                $orderColumn = 'created_at';
                $orderDir = 'desc';
            }

            // Remove default ordering before applying custom ordering
            $query->getQuery()->orders = [];

            // Apply ordering
            if ($orderColumn === 'severity' && $hasSeverity) {
                $query->orderBy('activity_log.severity', $orderDir);
            } elseif ($orderColumn === 'event_type' && $hasEventType) {
                $query->orderBy('activity_log.event_type', $orderDir);
            } elseif ($orderColumn === 'route_name' && $hasRouteName) {
                $query->orderBy('activity_log.route_name', $orderDir);
            } else {
                $query->orderBy('activity_log.'.$orderColumn, $orderDir);
            }

            // Apply pagination
            $logs = $query->skip($start)->take($length)->get();

            // Transform to DataTables format
            $data = $logs->map(function ($log) {
                $actorEmail = $log->actor_email ?? 'System';
                $actorName = $log->actor_fullname ??
                    trim(implode(' ', array_filter([$log->actor_firstname, $log->actor_lastname]))) ??
                    'N/A';
                $targetName = $log->target_fullname ??
                    trim(implode(' ', array_filter([$log->target_firstname ?? null, $log->target_lastname ?? null]))) ??
                    null;
                $targetAccount = $log->target_email ?? $log->target_personal_email ?? null;
                $requestSource = trim(implode(' ', array_filter([
                    $log->http_method ?? null,
                    $log->route_name ?? null,
                    $log->ip_address ?? null,
                ])));

                return [
                    'log_id' => $log->log_id,
                    'created_at' => $log->created_at ? $log->created_at->format('m/d/Y h:i A') : '',
                    'severity' => strtoupper((string) ($log->severity ?? 'info')),
                    'event_type' => $log->event_type ?? 'general',
                    'activity' => $log->activity ?? '',
                    'actor' => trim($actorName.' ('.$actorEmail.')'),
                    'target' => $targetName || $targetAccount
                        ? trim(implode(' ', array_filter([$targetName, $targetAccount ? "({$targetAccount})" : null])))
                        : 'N/A',
                    'module' => $log->module ?? '',
                    'request_source' => $requestSource !== '' ? $requestSource : 'N/A',
                    '_raw' => $log,
                ];
            });

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Log::error('Activity Log DataTables Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'draw' => (int) $request->get('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'An error occurred while processing your request. Please try again.',
            ], 500);
        }
    }

    /**
     * @return array<int, string>
     */
    private function distinctActivityLogValues(string $column): array
    {
        if (! Schema::hasTable('activity_log') || ! Schema::hasColumn('activity_log', $column)) {
            return [];
        }

        return ActivityLog::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->orderBy($column)
            ->distinct()
            ->pluck($column)
            ->map(fn (string $value): string => trim($value))
            ->filter(fn (string $value): bool => $value !== '')
            ->values()
            ->all();
    }
}
