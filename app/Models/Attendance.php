<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $table = 'tbl_attendance';

    public $timestamps = false;

    protected $fillable = [
        'hrid',
        'time_in',
        'time_in_remarks',
        'time_out',
        'time_out_remarks',
    ];

    protected $casts = [
        'id' => 'integer',
        'hrid' => 'integer',
        'time_in' => 'datetime',
        'time_out' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'hrid', 'hrid');
    }
}
