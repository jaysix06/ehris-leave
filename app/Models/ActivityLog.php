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
        'fk_user_id',
        'activity',
        'module',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'log_id' => 'integer',
            'fk_user_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fk_user_id', 'userId');
    }
}
