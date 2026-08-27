<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class AssessmentSubmission extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'assessment_submissions';

    protected $fillable = [
        'assessment_paper_id',
        'application_id',
        'student_id',
        'answer_file',
        'score',
        'result',
        'grader_feedback',
        'graded_by',
        'submitted_at',
        'graded_at',
        'status',

        // Evaluator 1 specific
        'evaluator_1_score',
        'evaluator_1_result',
        'evaluator_1_feedback',
        'evaluator_1_graded_at',
        'evaluator_1_clo1',
        'evaluator_1_clo2',
        'evaluator_1_clo3',
        'evaluator_1_clo4',

        // Evaluator 2 specific
        'evaluator_2_score',
        'evaluator_2_result',
        'evaluator_2_feedback',
        'evaluator_2_graded_at',
        'evaluator_2_clo1',
        'evaluator_2_clo2',
        'evaluator_2_clo3',
        'evaluator_2_clo4',
    ];

    /**
     * Mirrors the discipline already applied on Application. Without these the
     * BSON dates reach Blade unconverted and render as epoch milliseconds.
     */
    protected $casts = [
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'evaluator_1_graded_at' => 'datetime',
        'evaluator_2_graded_at' => 'datetime',
    ];
}
