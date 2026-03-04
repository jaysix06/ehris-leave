<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpEducationInfo extends Model
{
    protected $table = 'tbl_emp_education_info';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'hrid',
        'education_level',
        'school_name',
        'course',
        'from_year',
        'to_year',
        'year_graduated',
        'highest_grade',
        'scholarship',
    ];
}
