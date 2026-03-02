<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessUnit extends Model
{
    protected $table = 'tbl_business_unit';
    
    protected $fillable = [
        'office_id',
        'BusinessUnitId',
        'BusinessUnit',
    ];
    
    public $timestamps = false;
}
