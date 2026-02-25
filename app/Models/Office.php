<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Office extends Model
{
    protected $table = 'tbl_office';

    protected $primaryKey = 'id';

    public $timestamps = false;
}
