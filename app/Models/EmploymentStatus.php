<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmploymentStatus extends Model
{
    protected $table = 'tbl_employment_status';
    
    protected $fillable = [
        'emp_status',
    ];
    
    public $timestamps = false;
}
