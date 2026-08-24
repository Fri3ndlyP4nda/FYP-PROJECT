<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class AssessmentPaper extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'assessment_papers';

    protected $fillable = [
        'application_id',
        'evaluator_id',
        'title',
        'instructions',
        'question_file',
        'status',
        'parent_paper_id',
        'submission_deadline',
        'created_at',
        'updated_at',
    ];
}
