<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $table = 'activity_log';

    protected $primaryKey = 'log_id';

    public $timestamps = false;

    protected $fillable = [
        'log_id',
        'fk_user_id',
        'activity',
        'module',
        'severity',
        'event_type',
        'target_user_id',
        'ip_address',
        'http_method',
        'route_name',
        'user_agent',
        'context',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'log_id' => 'integer',
            'fk_user_id' => 'integer',
            'target_user_id' => 'integer',
            'context' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fk_user_id', 'userId');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id', 'userId');
    }
}
