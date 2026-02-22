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
