<?php

namespace App\Http\Controllers\SelfService;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class TimeLogsController extends Controller
{
    public function index(Request $request): Response
    {
        $logsByYear = [];

        if (Schema::hasTable('tbl_attendance')) {
            $hrid = $this->resolveHrid($request->user());
            if ($hrid > 0) {
                $records = Attendance::query()
                    ->where('hrid', $hrid)
                    ->whereNotNull('time_in')
                    ->whereNotNull('time_out')
                    ->orderByDesc('time_in')
                    ->get();

                foreach ($records as $record) {
                    $timeIn = $record->time_in;
                    $timeOut = $record->time_out;
                    $year = (int) $timeIn->format('Y');
                    $week = (int) $timeIn->weekOfYear;
                    $seconds = $timeIn->diffInSeconds($timeOut);
                    $hoursFormatted = $this->formatHours($seconds);

                    if (! isset($logsByYear[$year])) {
                        $logsByYear[$year] = [];
                    }
                    if (! isset($logsByYear[$year][$week])) {
                        $logsByYear[$year][$week] = ['entries' => [], 'totalSeconds' => 0];
                    }

                    $logsByYear[$year][$week]['entries'][] = [
                        'year' => $year,
                        'date_in' => $timeIn->format('F d, Y'),
                        'time_in' => $timeIn->format('h:i A'),
                        'date_out' => $timeOut->format('F d, Y'),
                        'time_out' => $timeOut->format('h:i A'),
                        'hours' => $hoursFormatted,
                    ];
                    $logsByYear[$year][$week]['totalSeconds'] += $seconds;
                }

                foreach (array_keys($logsByYear) as $y) {
                    foreach (array_keys($logsByYear[$y]) as $w) {
                        $logsByYear[$y][$w]['total'] = $this->formatHoursFull($logsByYear[$y][$w]['totalSeconds']);
                        unset($logsByYear[$y][$w]['totalSeconds']);
                    }
                }
            }
        }

        $years = array_keys($logsByYear);
        rsort($years);

        return Inertia::render('SelfService/TimeLogs', [
            'logsByYear' => $logsByYear,
            'years' => $years,
        ]);
    }

    private function formatHours(int $totalSeconds): string
    {
        $hours = (int) floor($totalSeconds / 3600);
        $minutes = (int) floor(($totalSeconds % 3600) / 60);

        return sprintf('%d:%02d', $hours, $minutes);
    }

    private function formatHoursFull(int $totalSeconds): string
    {
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

        if (! empty($authUser->hrId) && (int) $authUser->hrId > 0) {
            return (int) $authUser->hrId;
        }

        if (Schema::hasTable('tbl_user')) {
            $profile = User::query()
                ->select('hrId', 'userId')
                ->where('email', $authUser->email ?? '')
                ->first();

            if ($profile && ! empty($profile->hrId) && (int) $profile->hrId > 0) {
                return (int) $profile->hrId;
            }
        }

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

        $userId = $authUser->userId ?? $authUser->id ?? $authUser->getKey();
        if ($userId !== null && (int) $userId > 0) {
            return (int) $userId;
        }

        return 0;
    }
}
