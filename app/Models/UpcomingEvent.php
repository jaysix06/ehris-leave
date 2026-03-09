<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UpcomingEvent extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'start_at',
        'end_at',
        'description',
        'color',
        'indicator',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'userId');
    }
}
