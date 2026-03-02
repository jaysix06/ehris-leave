<?php

namespace App\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index()
    {
        return inertia('Utilities/ActivityLog');
    }

    public function datatables(Request $request)
    {
        try {
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
                ->leftJoin('tbl_user', 'activity_log.fk_user_id', '=', 'tbl_user.userId')
                ->select(
                    'activity_log.log_id',
                    'activity_log.fk_user_id',
                    'activity_log.activity',
                    'activity_log.module',
                    'activity_log.created_at',
                    'tbl_user.email',
                    'tbl_user.fullname',
                    'tbl_user.firstname',
                    'tbl_user.lastname'
                )
                ->orderBy('activity_log.created_at', 'desc'); // Default to newest first

            // Handle DataTables search parameter (global search)
            $searchValue = $request->input('search.value');

            if ($searchValue && trim($searchValue) !== '') {
                $searchTerm = trim($searchValue);
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('activity_log.activity', 'like', '%'.$searchTerm.'%')
                        ->orWhere('activity_log.module', 'like', '%'.$searchTerm.'%')
                        ->orWhere('tbl_user.email', 'like', '%'.$searchTerm.'%')
                        ->orWhere('tbl_user.fullname', 'like', '%'.$searchTerm.'%')
                        ->orWhere('tbl_user.firstname', 'like', '%'.$searchTerm.'%')
                        ->orWhere('tbl_user.lastname', 'like', '%'.$searchTerm.'%');
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
            $orderColumnIndex = (int) ($request->input('order.0.column', 0));
            $orderDir = $request->input('order.0.dir', 'desc'); // Default to desc for newest first

            // Map column index to database column
            // Column order: created_at, activity, email, module
            $columns = [
                'created_at',
                'activity',
                'email',
                'module',
            ];
            $orderColumn = $columns[$orderColumnIndex] ?? 'created_at';

            // Remove default ordering before applying custom ordering
            $query->getQuery()->orders = [];

            // Apply ordering
            if ($orderColumn === 'email') {
                $query->orderBy('tbl_user.email', $orderDir);
            } else {
                $query->orderBy('activity_log.'.$orderColumn, $orderDir);
            }

            // Apply pagination
            $logs = $query->skip($start)->take($length)->get();

            // Transform to DataTables format
            $data = $logs->map(function ($log) {
                $userEmail = $log->email ?? 'N/A';
                $userName = $log->fullname ??
                    trim(implode(' ', array_filter([$log->firstname, $log->lastname]))) ??
                    'N/A';

                return [
                    'log_id' => $log->log_id,
                    'created_at' => $log->created_at ? $log->created_at->format('m/d/Y h:i A') : '',
                    'activity' => $log->activity ?? '',
                    'email' => $userEmail,
                    'user_name' => $userName,
                    'module' => $log->module ?? '',
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
}
