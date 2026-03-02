<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $table = 'tbl_department';

    protected $primaryKey = 'id';

    protected $fillable = [
        'business_id',
        'department_id',
        'department_name',
        'department_abbrev',
    ];

    public $timestamps = false;
}
