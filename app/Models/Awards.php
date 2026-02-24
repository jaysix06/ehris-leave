<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Awards extends Model
{
    protected $table = 'tbl_awards';

    protected $primaryKey = 'id';

    public $timestamps = false;
}
