<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $table = 'tbl_leave_type';

    protected $primaryKey = 'id';

    public $timestamps = false;
}
