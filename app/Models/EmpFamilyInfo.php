<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpFamilyInfo extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_emp_family_info';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
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
        'deceased',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'hrid' => 'integer',
        ];
    }

    /**
     * Get the user (employee) that owns this family member record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hrid', 'hrId');
    }
}
