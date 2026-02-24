<?php

namespace App\Models;

use App\Events\LeaveTypeUpdated;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $table = 'tbl_leave_type';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'leave',
        'leave_type',
    ];

    protected static function booted(): void
    {
        static::saved(function () {
            LeaveTypeUpdated::dispatch();
        });

        static::deleted(function () {
            LeaveTypeUpdated::dispatch();
        });
    }
}
