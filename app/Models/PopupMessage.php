<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PopupMessage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'message',
        'link',
        'status',
    ];

    /**
     * Get status as string (Active/Inactive)
     */
    public function getStatusAttribute($value): string
    {
        return $value == 1 ? 'Active' : 'Inactive';
    }

    /**
     * Set status from string (Active/Inactive) to integer
     */
    public function setStatusAttribute($value): void
    {
        $this->attributes['status'] = ($value === 'Active' || $value === 1 || $value === '1') ? 1 : 0;
    }
}
