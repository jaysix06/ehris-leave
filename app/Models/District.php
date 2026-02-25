<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $table = 'tbl_district';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'district_code',
        'district_name',
    ];

    protected $casts = [
        'district_code' => 'integer',
    ];
}
