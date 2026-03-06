<?php

namespace App\Models;

use App\Events\LeaveTypeUpdated;
use App\Services\LeaveWorkflowNotificationService;
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
        static::saved(function (self $leaveType) {
            app(LeaveWorkflowNotificationService::class)->notifyLeaveTypeUpdated(
                $leaveType->leave_type,
                $leaveType->wasRecentlyCreated ? 'created' : 'updated',
            );
            LeaveTypeUpdated::dispatch();
        });

        static::deleted(function (self $leaveType) {
            app(LeaveWorkflowNotificationService::class)->notifyLeaveTypeUpdated(
                $leaveType->leave_type,
                'deleted',
            );
            LeaveTypeUpdated::dispatch();
        });
    }
}
