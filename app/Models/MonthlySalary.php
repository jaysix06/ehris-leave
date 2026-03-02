<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlySalary extends Model
{
    protected $table = 'tbl_monthly_salary';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = ['salary_grade', 'salary_step', 'salary_amount'];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'salary_grade' => 'integer',
            'salary_step' => 'integer',
            'salary_amount' => 'decimal:2',
        ];
    }
}
