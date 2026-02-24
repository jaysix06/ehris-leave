<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $table = 'tbl_emp_official_info';

    protected $primaryKey = 'hrid';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'hrid',
        'employee_id',
        'firstname',
        'middlename',
        'lastname',
        'extension',
        'job_title',
        'office',
        'station_code',
        'salary_grade',
        'salary_step',
        'employ_status',
        'subject_taught',
        'grade_level',
        'leave_balance',
    ];

    protected $casts = [
        'hrid' => 'integer',
        'employee_id' => 'integer',
        'salary_grade' => 'integer',
        'salary_step' => 'integer',
        'leave_balance' => 'decimal:2',
    ];
}
