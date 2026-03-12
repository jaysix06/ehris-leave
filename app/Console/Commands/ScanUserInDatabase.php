<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ScanUserInDatabase extends Command
{
    protected $signature = 'user:scan {id : User ID (userId) or email to look up}';

    protected $description = 'Scan a user in the database for login-related fields (email, password set, active)';

    public function handle(): int
    {
        $id = $this->argument('id');

        $user = is_numeric($id)
            ? User::query()->where('userId', (int) $id)->first()
            : User::query()
                ->whereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim($id))])
                ->orWhereRaw('LOWER(TRIM(personal_email)) = ?', [strtolower(trim($id))])
                ->first();

        if (! $user) {
            $this->error("User not found: {$id}");
            return self::FAILURE;
        }

        $passwordSet = isset($user->password) && is_string($user->password) && $user->password !== '';
        $passwordNote = $passwordSet ? 'yes (hash set)' : 'NO - cannot login until set (use Reset password in User List)';

        $this->table(
            ['Field', 'Value'],
            [
                ['userId', $user->userId],
                ['hrId', $user->hrId ?? '—'],
                ['email (official)', $user->email ?? '—'],
                ['personal_email', $user->personal_email ?? '—'],
                ['fullname', $user->fullname ?? '—'],
                ['firstname', $user->firstname ?? '—'],
                ['middlename', $user->middlename ?? '—'],
                ['lastname', $user->lastname ?? '—'],
                ['extname', $user->extname ?? '—'],
                ['active', $user->active ? '1 (Active)' : '0 (Inactive)'],
                ['password set?', $passwordNote],
                ['role', $user->role ?? '—'],
            ]
        );

        if (! $passwordSet) {
            $this->warn('This user cannot log in: password is missing. Use Utilities → User List → Reset password for this user.');
        }

        return self::SUCCESS;
    }
}
