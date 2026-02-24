<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $table = 'tbl_document';

    protected $primaryKey = 'id';

    public $timestamps = false;
}
