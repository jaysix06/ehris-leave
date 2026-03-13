<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OnlineUserService
{
    /**
     * @param  iterable<int|string>  $userIds
     * @return array<int, bool>
     */
    public function lookupForUserIds(iterable $userIds): array
    {
        $normalizedIds = Collection::make($userIds)
            ->map(fn ($userId): int => (int) $userId)
            ->filter(fn (int $userId): bool => $userId > 0)
            ->unique()
            ->values();

        if ($normalizedIds->isEmpty()) {
            return [];
        }

        if (config('session.driver') !== 'database') {
            return [];
        }

        $sessionTable = (string) config('session.table', 'sessions');

        if (! Schema::hasTable($sessionTable)) {
            return [];
        }

        $lastActivityThreshold = now()
            ->subMinutes((int) config('session.lifetime', 120))
            ->getTimestamp();

        return DB::table($sessionTable)
            ->whereNotNull('user_id')
            ->whereIn('user_id', $normalizedIds->all())
            ->where('last_activity', '>=', $lastActivityThreshold)
            ->distinct()
            ->pluck('user_id')
            ->mapWithKeys(fn ($userId): array => [(int) $userId => true])
            ->all();
    }
}
