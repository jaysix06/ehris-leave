<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobTitle extends Model
{
    protected $table = 'tbl_job_title';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = ['job_title', 'job_shorten'];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
        ];
    }
}
