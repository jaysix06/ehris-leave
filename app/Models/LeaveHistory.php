<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveHistory extends Model
{
    protected $table = 'tbl_leave_history';

    protected $primaryKey = 'id';

    public $timestamps = false;
}
