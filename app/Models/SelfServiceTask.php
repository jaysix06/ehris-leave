<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SelfServiceTask extends Model
{
    protected $table = 'tbl_self_service_tasks';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'priority',
        'due_date',
        'due_date_end',
        'add_to_calendar',
        'status',
        'accomplishment_report',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'due_date_end' => 'date',
            'add_to_calendar' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
