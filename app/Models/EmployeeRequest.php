<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeRequest extends Model
{
    protected $table = 'tbl_requests';

    public $timestamps = false;

    protected $fillable = [
        'hrid',
        'purpose',
        'attachment',
        'status',
        'type_of_request',
        'reason',
        'running_year',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'hrid' => 'integer',
        ];
    }
}
