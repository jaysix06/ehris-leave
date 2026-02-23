<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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
