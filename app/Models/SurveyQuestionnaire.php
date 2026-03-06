<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyQuestionnaire extends Model
{
    protected $table = 'tbl_survey_questionnaire';

    protected $primaryKey = 'survey_question_id';

    protected $fillable = [
        'question',
        'frm_option',
        'type',
        'survey_id',
    ];

    public $timestamps = false;

    protected $casts = [
        'date_created' => 'datetime',
    ];

    public function surveySet()
    {
        return $this->belongsTo(SurveySet::class, 'survey_id', 'id');
    }
}
