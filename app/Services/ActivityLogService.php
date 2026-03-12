<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class ActivityLogService
{
    /**
     * Log a user activity
     */
    public static function log(string $activity, string $module, ?int $userId = null): void
    {
        $userId = $userId ?? Auth::id();

        if ($userId) {
            try {
                ActivityLog::create([
                    'fk_user_id' => $userId,
                    'activity' => $activity,
                    'module' => $module,
                    'created_at' => now(),
                ]);
            } catch (Throwable $exception) {
                Log::warning('Activity log write failed.', [
                    'user_id' => $userId,
                    'module' => $module,
                    'activity' => $activity,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }
    }

    /**
     * Log login activity
     */
    public static function logLogin(?int $userId = null): void
    {
        $user = $userId ? \App\Models\User::find($userId) : Auth::user();

        if ($user) {
            $dateTime = now()->format('l d F Y h:i:s A');
            $activity = "Login : {$dateTime} ({$user->email})";
            self::log($activity, 'Login Management', $user->userId);
        }
    }

    /**
     * Log logout activity
     */
    public static function logLogout(?int $userId = null): void
    {
        $user = $userId ? \App\Models\User::find($userId) : Auth::user();

        if ($user) {
            $dateTime = now()->format('l d F Y h:i:s A');
            $activity = "Logout : {$dateTime} ({$user->email})";
            self::log($activity, 'Logout Management', $user->userId);
        }
    }

    /**
     * Log password reset activity
     *
     * @param  string|null  $targetUserEmail  The email of the user whose password was reset
     * @param  int|null  $userId  The ID of the user performing the reset (admin or self)
     */
    public static function logPasswordReset(?string $targetUserEmail = null, ?int $userId = null): void
    {
        $user = $userId ? \App\Models\User::find($userId) : Auth::user();

        if ($user) {
            // If target email is provided, format like: "Reset user email@deped.gov.ph's password"
            // Otherwise, format like: "Reset password"
            if ($targetUserEmail) {
                $activity = "Reset user {$targetUserEmail}'s password";
            } else {
                $activity = 'Reset password';
            }
            self::log($activity, 'User Management', $user->userId);
        }
    }

    /**
     * Log update activity
     */
    public static function logUpdate(string $resource, string $details = '', ?int $userId = null): void
    {
        $user = $userId ? \App\Models\User::find($userId) : Auth::user();

        if ($user) {
            $activity = "Updated {$resource}";
            if ($details) {
                $activity .= " : {$details}";
            }
            self::log($activity, $resource.' Management', $user->userId);
        }
    }

    /**
     * Log delete activity
     */
    public static function logDelete(string $resource, string $details = '', ?int $userId = null): void
    {
        $user = $userId ? \App\Models\User::find($userId) : Auth::user();

        if ($user) {
            $activity = "Deleted {$resource}";
            if ($details) {
                $activity .= " : {$details}";
            }
            self::log($activity, $resource.' Management', $user->userId);
        }
    }

    /**
     * Log create activity
     */
    public static function logCreate(string $resource, string $details = '', ?int $userId = null): void
    {
        $user = $userId ? \App\Models\User::find($userId) : Auth::user();

        if ($user) {
            $activity = "Created {$resource}";
            if ($details) {
                $activity .= " : {$details}";
            }
            self::log($activity, $resource.' Management', $user->userId);
        }
    }
}
