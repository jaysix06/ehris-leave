<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'tbl_role';

    protected $fillable = ['role_name'];

    public $timestamps = false;

    /**
     * All role names ordered by id (for dropdowns, validation, etc.).
     *
     * @return array<int, string>
     */
    public static function roleNames(): array
    {
        return static::query()
            ->orderBy('id')
            ->pluck('role_name')
            ->filter(fn ($name) => trim((string) $name) !== '')
            ->values()
            ->all();
    }
}
