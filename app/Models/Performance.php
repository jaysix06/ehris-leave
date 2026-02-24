<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Performance extends Model
{
    protected $table = 'tbl_performance';

    protected $primaryKey = 'id';

    public $timestamps = false;
}
