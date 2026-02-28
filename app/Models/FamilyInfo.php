<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyInfo extends Model
{
    protected $table = 'tbl_emp_family_info';

    public $timestamps = false;

    protected $fillable = [
        'hrid',
        'relationship',
        'firstname',
        'middlename',
        'lastname',
        'extension',
        'dob',
        'occupation',
        'employer_name',
        'business_add',
        'tel_num',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hrid', 'hrId');
    }
}
