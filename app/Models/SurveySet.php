<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveySet extends Model
{
    protected $table = 'tbl_survey_set';

    protected $fillable = [
        'title',
        'description',
        'category',
        'userId',
        'date_created',
    ];

    public $timestamps = false;

    protected $casts = [
        'date_created' => 'datetime',
    ];

    public function questions()
    {
        return $this->hasMany(SurveyQuestionnaire::class, 'survey_id', 'id');
    }
}
