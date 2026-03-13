<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\PopupMessage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const LEAVE_TABLE = 'tbl_leave_applications';

    public function __invoke(Request $request): Response
    {
        $activePopups = [];
        $showPopups = false;
        $dashboardAttendance = $this->defaultAttendance();
        $dashboardAttendanceTrends = $this->defaultAttendanceTrends();
        $overviewStats = $this->buildOverviewStats();

        if ($request->session()->get('show_popups_after_login', false)) {
            $activePopups = PopupMessage::query()
                ->where('status', 1)
                ->orderByDesc('created_at')
                ->get();

            $showPopups = true;
            $request->session()->forget('show_popups_after_login');
        }

        if ($request->user() !== null && Schema::hasTable('tbl_attendance')) {
            $hrid = $this->resolveHrid($request->user());

            if ($hrid > 0) {
                $dashboardAttendance = $this->buildAttendanceState($hrid);
                $dashboardAttendanceTrends = $this->buildAttendanceTrends($hrid);
            }
        }

        return Inertia::render('Dashboard', [
            'activePopups' => $activePopups,
            'showPopups' => $showPopups,
            'dashboardAttendance' => $dashboardAttendance,
            'dashboardAttendanceTrends' => $dashboardAttendanceTrends,
            'overviewStats' => $overviewStats,
        ]);
    }

    /**
     * @return array{isClockedIn: bool, hoursWorkedThisWeek: string, lastTimeIn: ?string, lastTimeOut: ?string}
     */
    private function defaultAttendance(): array
    {
        return [
            'isClockedIn' => false,
            'hoursWorkedThisWeek' => '00:00:00',
            'lastTimeIn' => null,
            'lastTimeOut' => null,
        ];
    }

    /**
     * @return array{recentTimeline: array<int, int>, monthlyLateCount: int, monthlyUndertimeCount: int}
     */
    private function defaultAttendanceTrends(): array
    {
        return [
            'recentTimeline' => [0, 0, 0, 0, 0, 0, 0],
            'monthlyLateCount' => 0,
            'monthlyUndertimeCount' => 0,
        ];
    }

    /**
     * @return array{activeEmployees: int, pendingRequests: int, currentlyClockedIn: int, todayActivityLogs: int}
     */
    private function buildOverviewStats(): array
    {
        $activeEmployees = Schema::hasTable('tbl_user')
            ? User::query()->where('active', true)->count()
            : 0;

        $pendingRequests = Schema::hasTable(self::LEAVE_TABLE)
            ? \Illuminate\Support\Facades\DB::table(self::LEAVE_TABLE)
                ->whereIn('workflow_status', ['pending_rm', 'pending_hr', 'pending_sds'])
                ->count()
            : 0;

        $currentlyClockedIn = Schema::hasTable('tbl_attendance')
            ? Attendance::query()
                ->whereNull('time_out')
                ->distinct('hrid')
                ->count('hrid')
            : 0;

        $todayActivityLogs = Schema::hasTable('activity_log')
            ? ActivityLog::query()
                ->whereDate('created_at', now()->toDateString())
                ->count()
            : 0;

        return [
            'activeEmployees' => $activeEmployees,
            'pendingRequests' => $pendingRequests,
            'currentlyClockedIn' => $currentlyClockedIn,
            'todayActivityLogs' => $todayActivityLogs,
        ];
    }

    /**
     * @return array{isClockedIn: bool, hoursWorkedThisWeek: string, lastTimeIn: ?string, lastTimeOut: ?string}
     */
    private function buildAttendanceState(int $hrid): array
    {
        $openRecord = Attendance::query()
            ->where('hrid', $hrid)
            ->whereNull('time_out')
            ->latest('time_in')
            ->first();

        $lastRecord = Attendance::query()
            ->where('hrid', $hrid)
            ->latest('time_in')
            ->first();

        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        $weeklyRecords = Attendance::query()
            ->where('hrid', $hrid)
            ->whereNotNull('time_in')
            ->whereNotNull('time_out')
            ->where('time_in', '<=', $endOfWeek)
            ->where('time_out', '>=', $startOfWeek)
            ->get();

        $totalSeconds = 0;
        foreach ($weeklyRecords as $record) {
            $effectiveStart = $record->time_in->isBefore($startOfWeek) ? $startOfWeek : $record->time_in;
            $effectiveEnd = $record->time_out->isAfter($endOfWeek) ? $endOfWeek : $record->time_out;
            $totalSeconds += $effectiveStart->diffInSeconds($effectiveEnd);
        }

        $hours = (int) floor($totalSeconds / 3600);
        $minutes = (int) floor(($totalSeconds % 3600) / 60);
        $seconds = (int) ($totalSeconds % 60);

        return [
            'isClockedIn' => $openRecord !== null,
            'hoursWorkedThisWeek' => sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds),
            'lastTimeIn' => $openRecord?->time_in?->toIso8601String() ?? $lastRecord?->time_in?->toIso8601String(),
            'lastTimeOut' => $lastRecord?->time_out?->toIso8601String(),
        ];
    }

    /**
     * @return array{recentTimeline: array<int, int>, monthlyLateCount: int, monthlyUndertimeCount: int}
     */
    private function buildAttendanceTrends(int $hrid): array
    {
        $weekStart = now()->startOfWeek(Carbon::SUNDAY)->startOfDay();
        $weekEnd = now()->endOfWeek(Carbon::SATURDAY)->endOfDay();

        $recentAttendanceDates = Attendance::query()
            ->where('hrid', $hrid)
            ->whereNotNull('time_in')
            ->whereBetween('time_in', [$weekStart, $weekEnd])
            ->get(['time_in'])
            ->map(fn (Attendance $attendance): string => $attendance->time_in->toDateString())
            ->unique()
            ->values();

        $recentTimeline = [];
        for ($offset = 0; $offset <= 6; $offset++) {
            $date = $weekStart->copy()->addDays($offset)->toDateString();
            $recentTimeline[] = $recentAttendanceDates->contains($date) ? 1 : 0;
        }

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $monthlyRecords = Attendance::query()
            ->where('hrid', $hrid)
            ->whereNotNull('time_in')
            ->whereBetween('time_in', [$monthStart, $monthEnd])
            ->get(['time_in', 'time_in_remarks', 'time_out', 'time_out_remarks']);

        $lateCount = 0;
        $undertimeCount = 0;

        $recordsByDate = $monthlyRecords->groupBy(
            fn (Attendance $attendance): string => $attendance->time_in->toDateString()
        );

        foreach ($recordsByDate as $records) {
            $hasLate = $records->contains(function (Attendance $attendance): bool {
                $remarks = strtolower(trim((string) $attendance->time_in_remarks));
                if (str_contains($remarks, 'late')) {
                    return true;
                }

                if ($remarks !== '') {
                    return false;
                }

                return $attendance->time_in->gt($attendance->time_in->copy()->setTime(8, 0));
            });

            $hasUndertime = $records->contains(function (Attendance $attendance): bool {
                if ($attendance->time_out === null) {
                    return false;
                }

                $remarks = strtolower(trim((string) $attendance->time_out_remarks));
                if (str_contains($remarks, 'undertime') || str_contains($remarks, 'under time')) {
                    return true;
                }

                if ($remarks !== '') {
                    return false;
                }

                return $attendance->time_out->lt($attendance->time_out->copy()->setTime(17, 0));
            });

            if ($hasLate) {
                $lateCount++;
            }

            if ($hasUndertime) {
                $undertimeCount++;
            }
        }

        return [
            'recentTimeline' => $recentTimeline,
            'monthlyLateCount' => $lateCount,
            'monthlyUndertimeCount' => $undertimeCount,
        ];
    }

    private function resolveHrid(User $authUser): int
    {
        $hrid = (int) ($authUser->hrId ?? 0);
        if ($hrid > 0) {
            return $hrid;
        }

        if (Schema::hasTable('tbl_user')) {
            $profile = User::query()
                ->select('hrId', 'userId')
                ->where('email', (string) ($authUser->email ?? ''))
                ->first();

            $hrid = (int) ($profile?->hrId ?? 0);
            if ($hrid > 0) {
                return $hrid;
            }

            $userId = (int) ($profile?->userId ?? 0);
            if ($userId > 0) {
                return $userId;
            }
        }

        if (Schema::hasTable('tbl_emp_official_info') && Schema::hasColumn('tbl_emp_official_info', 'email')) {
            $employee = Employee::query()
                ->select('hrid')
                ->where('email', (string) ($authUser->email ?? ''))
                ->first();
            $hrid = (int) ($employee?->hrid ?? 0);

            if ($hrid > 0) {
                return $hrid;
            }
        }

        return (int) ($authUser->userId ?? $authUser->id ?? 0);
    }
}
