<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyInfo extends Model
{
    // 1. Tell Eloquent the exact table name
    protected $table = 'tbl_emp_family_info';

    // 2. Disable timestamps if your table doesn't have created_at/updated_at
    public $timestamps = false;

    // 3. Define which fields can be mass-assigned
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

    /**
     * Relationship back to the User/Employee
     */
    public function user(): BelongsTo
    {
        // Assuming 'hrid' is the foreign key on this table
        return $this->belongsTo(User::class, 'hrid', 'hrId');
    }
}