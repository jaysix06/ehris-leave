<?php

namespace App\Http\Controllers\SelfService;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class TimezoneController extends Controller
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

        return Inertia::render('SelfService/Timezone', [
            'attendance' => [
                'isClockedIn' => $isClockedIn,
                'hoursWorkedThisWeek' => $hoursWorkedThisWeek,
            ],
        ]);
    }

    public function clockIn(Request $request): Response
    {
        $attendance = $this->getAttendanceState($request);

        if (! Schema::hasTable('tbl_attendance')) {
            return Inertia::render('SelfService/Timezone', [
                'attendance' => $attendance,
                'errorMessage' => 'Attendance table is not available.',
            ]);
        }

        $hrid = $this->resolveHrid($request->user());
        if ($hrid <= 0) {
            return Inertia::render('SelfService/Timezone', [
                'attendance' => $attendance,
                'errorMessage' => 'Unable to identify employee.',
            ]);
        }

        $openRecord = Attendance::where('hrid', $hrid)->whereNull('time_out')->orderByDesc('time_in')->first();
        if ($openRecord !== null) {
            return Inertia::render('SelfService/Timezone', [
                'attendance' => $attendance,
                'errorMessage' => 'You are already clocked in. Clock out first.',
            ]);
        }

        Attendance::create([
            'hrid' => $hrid,
            'time_in' => now(),
        ]);

        $hoursWorkedThisWeek = $this->getHoursWorkedThisWeek($hrid);

        return Inertia::render('SelfService/Timezone', [
            'attendance' => [
                'isClockedIn' => true,
                'hoursWorkedThisWeek' => $hoursWorkedThisWeek,
            ],
            'successMessage' => 'Clocked in successfully.',
        ]);
    }

    public function clockOut(Request $request): Response
    {
        $attendance = $this->getAttendanceState($request);

        if (! Schema::hasTable('tbl_attendance')) {
            return Inertia::render('SelfService/Timezone', [
                'attendance' => $attendance,
                'errorMessage' => 'Attendance table is not available.',
            ]);
        }

        $hrid = $this->resolveHrid($request->user());
        if ($hrid <= 0) {
            return Inertia::render('SelfService/Timezone', [
                'attendance' => $attendance,
                'errorMessage' => 'Unable to identify employee.',
            ]);
        }

        $openRecord = Attendance::where('hrid', $hrid)->whereNull('time_out')->orderByDesc('time_in')->first();
        if ($openRecord === null) {
            return Inertia::render('SelfService/Timezone', [
                'attendance' => $attendance,
                'errorMessage' => 'You are not clocked in.',
            ]);
        }

        $openRecord->update(['time_out' => now()]);

        $hoursWorkedThisWeek = $this->getHoursWorkedThisWeek($hrid);

        return Inertia::render('SelfService/Timezone', [
            'attendance' => [
                'isClockedIn' => false,
                'hoursWorkedThisWeek' => $hoursWorkedThisWeek,
            ],
            'successMessage' => 'Clocked out successfully.',
        ]);
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
