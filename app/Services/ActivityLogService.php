<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class ActivityLogService
{
    /**
     * Log a user activity
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function log(string $activity, string $module, ?int $userId = null, array $attributes = []): void
    {
        $userId = $userId ?? Auth::id() ?? 0;

        try {
            $payload = [
                'fk_user_id' => $userId,
                'activity' => Str::limit(trim($activity), 255, '...'),
                'module' => Str::limit(trim($module), 255, '...'),
                'created_at' => now(),
            ];

            $columnMap = [
                'severity' => 'severity',
                'event_type' => 'event_type',
                'target_user_id' => 'target_user_id',
                'ip_address' => 'ip_address',
                'http_method' => 'http_method',
                'route_name' => 'route_name',
                'user_agent' => 'user_agent',
                'context' => 'context',
            ];

            foreach ($columnMap as $attributeKey => $column) {
                if (! array_key_exists($attributeKey, $attributes) || ! Schema::hasColumn('activity_log', $column)) {
                    continue;
                }

                $payload[$column] = $attributeKey === 'context'
                    ? ($normalizedContext = self::normalizeContextArray($attributes[$attributeKey])) !== null
                        ? json_encode($normalizedContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                        : null
                    : $attributes[$attributeKey];
            }

            if (Schema::hasTable('activity_log') && Schema::hasColumn('activity_log', 'log_id')) {
                $payload['log_id'] = (int) DB::table('activity_log')->max('log_id') + 1;
            }

            DB::table('activity_log')->insert($payload);
        } catch (Throwable $exception) {
            Log::warning('Activity log write failed.', [
                'user_id' => $userId,
                'module' => $module,
                'activity' => $activity,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function logSecurityEvent(
        string $activity,
        string $module = 'Security Monitoring',
        ?int $userId = null,
        array $context = [],
        array $attributes = [],
    ): void {
        $parts = [trim($activity)];

        foreach ($context as $key => $value) {
            $normalizedValue = self::normalizeContextValue($value);
            if ($normalizedValue === '') {
                continue;
            }

            $parts[] = "{$key}: {$normalizedValue}";
        }

        self::log(implode(' | ', $parts), $module, $userId, [
            'severity' => $attributes['severity'] ?? 'info',
            'event_type' => $attributes['event_type'] ?? null,
            'target_user_id' => $attributes['target_user_id'] ?? null,
            'ip_address' => $attributes['ip_address'] ?? null,
            'http_method' => $attributes['http_method'] ?? null,
            'route_name' => $attributes['route_name'] ?? null,
            'user_agent' => $attributes['user_agent'] ?? null,
            'context' => $attributes['context'] ?? $context,
        ]);
    }

    public static function logAuthenticationFailure(
        string $email,
        string $reason,
        ?Request $request = null,
        ?int $userId = null,
    ): void {
        self::logSecurityEvent(
            'Authentication failed',
            'Authentication Security',
            $userId,
            [
                'email' => $email !== '' ? Str::lower(trim($email)) : 'blank',
                'reason' => $reason,
            ],
            [
                'severity' => 'warning',
                'event_type' => 'authentication_failed',
                ...self::requestAttributes($request),
            ],
        );
    }

    public static function logCredentialRecoveryEvent(
        string $activity,
        string $email,
        ?Request $request = null,
        ?int $userId = null,
        array $context = [],
    ): void {
        self::logSecurityEvent(
            $activity,
            'Credential Recovery',
            $userId,
            [
                'email' => $email !== '' ? Str::lower(trim($email)) : 'blank',
                ...$context,
            ],
            [
                'severity' => 'warning',
                'event_type' => 'credential_recovery',
                ...self::requestAttributes($request),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function logSensitiveUserAccountChange(
        string $activity,
        User $targetUser,
        array $context = [],
        ?int $actorUserId = null,
    ): void {
        self::logSecurityEvent(
            $activity,
            'User Management',
            $actorUserId,
            [
                'target_user_id' => $targetUser->getKey(),
                'target_account' => $targetUser->email ?: $targetUser->personal_email,
                ...$context,
            ],
            [
                'severity' => 'warning',
                'event_type' => 'user_account_change',
                'target_user_id' => $targetUser->getKey(),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function logExportAction(
        string $activity,
        array $context = [],
        ?int $userId = null,
    ): void {
        self::logSecurityEvent($activity, 'Report Export', $userId, $context, [
            'severity' => 'info',
            'event_type' => 'data_export',
        ]);
    }

    /**
     * @return array<string, string|null>
     */
    private static function requestAttributes(?Request $request): array
    {
        if ($request === null) {
            return [];
        }

        $userAgent = trim((string) $request->userAgent());

        return [
            'ip_address' => $request->ip(),
            'http_method' => $request->method(),
            'route_name' => optional($request->route())->getName() ?: $request->path(),
            'user_agent' => $userAgent !== '' ? Str::limit($userAgent, 500, '...') : null,
            'context' => [
                'ip' => $request->ip(),
                'method' => $request->method(),
                'route' => optional($request->route())->getName() ?: $request->path(),
            ],
        ];
    }

    private static function normalizeContextValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            $value = collect($value)
                ->map(fn (mixed $item): string => self::normalizeContextValue($item))
                ->filter(fn (string $item): bool => $item !== '')
                ->implode('; ');
        }

        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function normalizeContextArray(mixed $value): ?array
    {
        if (! is_array($value)) {
            return null;
        }

        $normalized = [];

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $normalized[$key] = self::normalizeContextArray($item);

                continue;
            }

            if ($item === null) {
                continue;
            }

            $normalized[$key] = self::normalizeContextValue($item);
        }

        return $normalized === [] ? null : $normalized;
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
