<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class AttendanceAutoClockOutCommand extends Command
{
    protected $signature = 'attendance:auto-clock-out';

    protected $description = 'Automatically clock out all users still clocked in at 6:00 PM GMT+8.';

    public function handle(): int
    {
        if (! Schema::hasTable('tbl_attendance')) {
            $this->warn('tbl_attendance table not found. Skipping auto clock-out.');

            return self::SUCCESS;
        }

        $autoClockOutTime = Carbon::now('Asia/Manila')->setTime(18, 0, 0);

        $affectedRows = Attendance::query()
            ->whereNull('time_out')
            ->update(['time_out' => $autoClockOutTime]);

        $this->info("Auto clock-out completed. Updated {$affectedRows} attendance record(s).");

        return self::SUCCESS;
    }
}
