<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

Artisan::command('user:fix-default-password {email}', function (string $email) {
    $user = User::query()
        ->where('email', '=', $email)
        ->orWhere('personal_email', '=', $email)
        ->first();

    if (! $user) {
        $this->error("User not found for email: {$email}");

        return self::FAILURE;
    }

    $defaultPassword = '1q2w3e4r5t';
    $user->password = Hash::make($defaultPassword);
    $user->save();

    $this->info("Password set to default activation password for: {$user->email}");
    $this->warn('User should change password after first login.');

    return self::SUCCESS;
})->purpose('Set a user\'s password to the default activation password (for users who could not log in after activation)');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('users:hash-legacy-passwords', function () {
    if (! DB::getSchemaBuilder()->hasTable('tbl_user')) {
        $this->error('Table tbl_user does not exist.');

        return self::FAILURE;
    }

    $updatedCount = 0;
    $skippedCount = 0;

    DB::table('tbl_user')
        ->select(['userId', 'password'])
        ->orderBy('userId')
        ->chunk(200, function ($users) use (&$updatedCount, &$skippedCount) {
            foreach ($users as $user) {
                $currentPassword = (string) ($user->password ?? '');
                if ($currentPassword === '') {
                    $skippedCount++;

                    continue;
                }

                $algoInfo = password_get_info($currentPassword);
                $isAlreadyHashed = ($algoInfo['algoName'] ?? 'unknown') !== 'unknown';

                if ($isAlreadyHashed) {
                    $skippedCount++;

                    continue;
                }

                DB::table('tbl_user')
                    ->where('userId', $user->userId)
                    ->update(['password' => Hash::make($currentPassword)]);

                $updatedCount++;
            }
        });

    $this->info("Done. Updated: {$updatedCount}, skipped: {$skippedCount}.");

    return self::SUCCESS;
})->purpose('Hash legacy plain-text passwords stored in tbl_user.password');

Artisan::command('users:reset-legacy-g-hashes {password}', function (string $password) {
    if (! DB::getSchemaBuilder()->hasTable('tbl_user')) {
        $this->error('Table tbl_user does not exist.');

        return self::FAILURE;
    }

    $updatedCount = 0;

    DB::table('tbl_user')
        ->select(['userId', 'password'])
        ->where('password', 'like', '$G$%')
        ->orderBy('userId')
        ->chunk(200, function ($users) use (&$updatedCount, $password) {
            foreach ($users as $user) {
                DB::table('tbl_user')
                    ->where('userId', $user->userId)
                    ->update(['password' => Hash::make($password)]);

                $updatedCount++;
            }
        });

    $this->info("Done. Reset {$updatedCount} legacy \$G\$ password(s) to bcrypt.");
    $this->warn('All affected users now share the same temporary password passed to this command.');

    return self::SUCCESS;
})->purpose('Reset legacy $G$ password hashes in tbl_user to Laravel bcrypt using a temporary password');

Artisan::command('leave:forfeit-mandatory-vl {year?}', function (?string $year = null) {
    if (! DB::getSchemaBuilder()->hasTable('tbl_request_leave') || ! DB::getSchemaBuilder()->hasTable('tbl_leave_history')) {
        $this->error('Required leave tables are missing.');

        return self::FAILURE;
    }

    $targetYear = (int) ($year ?: now()->format('Y'));
    $processed = 0;

    $hrids = DB::table('tbl_request_leave')
        ->select('hrid')
        ->whereNotNull('hrid')
        ->groupBy('hrid')
        ->pluck('hrid');

    foreach ($hrids as $hrid) {
        $usedMandatoryDays = (int) DB::table('tbl_request_leave')
            ->where('hrid', $hrid)
            ->whereYear('fdate', $targetYear)
            ->whereIn('leave_type', ['Vacation Leave', 'Mandatory/Force Leave', 'Mandatory Leave', 'Forced Leave'])
            ->sum('leave_count');

        $forfeited = max(5 - $usedMandatoryDays, 0);
        if ($forfeited <= 0) {
            continue;
        }

        $alreadyExists = DB::table('tbl_leave_history')
            ->where('hrid', $hrid)
            ->where('type', 'Mandatory Leave Forfeiture')
            ->whereYear('credits_from', $targetYear)
            ->exists();

        if ($alreadyExists) {
            continue;
        }

        DB::table('tbl_leave_history')->insert([
            'hrid' => $hrid,
            'credits_from' => "{$targetYear}-01-01",
            'credits_to' => "{$targetYear}-12-31",
            'no_of_days' => (string) $forfeited,
            'particulars' => 'Mandatory VL forfeiture',
            'type' => 'Mandatory Leave Forfeiture',
            'balance' => null,
            'remarks' => "Unused mandatory VL forfeited for {$targetYear}",
        ]);

        $processed++;
    }

    $this->info("Processed forfeiture entries: {$processed}");

    return self::SUCCESS;
})->purpose('Compute and post year-end mandatory/forced VL forfeiture records');
