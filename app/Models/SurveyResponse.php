<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyResponse extends Model
{
    protected $table = 'tbl_survey_response';

    protected $primaryKey = 'survey_response_id';

    public $timestamps = false;

    protected $fillable = [
        'survey_id',
        'userId',
        'answer',
        'question_id',
        'is_limit',
    ];

    protected $casts = [
        'date_created' => 'datetime',
        'is_limit' => 'boolean',
    ];

    public function surveySet()
    {
        return $this->belongsTo(SurveySet::class, 'survey_id', 'id');
    }
}
