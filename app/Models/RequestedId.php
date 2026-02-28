<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestedId extends Model
{
    protected $table = 'tbl_requested_id';

    protected $fillable = [
        'hrid',
        'user_id',
        'fullname',
        'email',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'hrid' => 'integer',
            'user_id' => 'integer',
        ];
    }
}
