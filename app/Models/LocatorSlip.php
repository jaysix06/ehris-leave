<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocatorSlip extends Model
{
    protected $table = 'tbl_locator_slips';

    protected $fillable = [
        'control_no',
        'hrid',
        'user_id',
        'rm_assignee_hrid',
        'date_of_filing',
        'employee_name',
        'position_designation',
        'permanent_station',
        'purpose_of_travel',
        'travel_type',
        'travel_date',
        'time_out',
        'time_in',
        'destination',
        'workflow_status',
        'rm_status',
        'rm_acted_by',
        'rm_action_at',
        'rm_remarks',
        'status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'hrid' => 'integer',
            'user_id' => 'integer',
            'rm_assignee_hrid' => 'integer',
            'rm_acted_by' => 'integer',
            'date_of_filing' => 'date',
            'travel_date' => 'date',
            'rm_action_at' => 'datetime',
        ];
    }
}
