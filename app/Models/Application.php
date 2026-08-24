<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Application extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'applications';

    protected $fillable = [
        'user_id',
        'student_id',
        'application_type',
        'program_applied',
        'submission_date',
        'status',
        'status_updated_at',

        'age',
        'university_name',
        'company_name',
        'ic_no',

        'supporting_docs',
        'evaluator_id',
        'evaluator_2_id',
        'assessment_type',
        'evaluator_feedback',
        'assigned_at',

        // Payment workflow
        'payment_status',
        'payment_type',
        'payment_reference',
        'payment_remarks',
        'payment_verified_at',
        'payment_receipt',

        // Advisor (APEL C pre-application) review
        'advisor_name',
        'advisor_approved_at',
        'mode_of_assessment',

        // APEL A workflow
        'review_stage',
        'admission_decision',
        'admission_remarks',
        'reviewed_at',
        'final_decision',
        'final_decision_remarks',
        'finalized_at',
        'evidence_file',
        'portfolio_file',

        // APEL A evaluator 1 specific
        'evaluator_1_decision',
        'evaluator_1_feedback',
        'evaluator_1_reviewed_at',

        // APEL A evaluator 2 specific
        'evaluator_2_decision',
        'evaluator_2_feedback',
        'evaluator_2_reviewed_at',

        // APEL C credit workflow
        'credit_status',
        'credit_decision',
        'credit_remarks',
        'credit_hours_approved',
        'credit_course_code',
        'credit_course_name',
        'credit_decided_at',

        // APEL A internal form
        'highest_qualification',
        'current_job',
        'working_experience_years',
        'working_experience_details',
        'reason_applying',

        // APEL C internal form
        'prior_learning_experience',
        'self_assessment_statement',
        'evidence_description',
        'portfolio_summary',

        // Appeal
        'appeal_status',
        'appeal_submitted_at',
        'appeal_remarks',

        // Structured APEL C fields & Recommendations
        'pre_app_data',
        'self_assessment',
        'advisor_evaluation',
        'portfolio_essays',
        'target_semester',
        'target_year',
    ];

    protected $casts = [
        'supporting_docs' => 'array',
        'submission_date' => 'datetime',
        'status_updated_at' => 'datetime',
        'assigned_at' => 'datetime',
        'advisor_approved_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'evaluator_1_reviewed_at' => 'datetime',
        'evaluator_2_reviewed_at' => 'datetime',
        'finalized_at' => 'datetime',
        'credit_decided_at' => 'datetime',
        'payment_verified_at' => 'datetime',
        'appeal_submitted_at' => 'datetime',
        'evidence_file' => 'array',
        'portfolio_file' => 'array',
        'pre_app_data' => 'array',
        'self_assessment' => 'array',
        'advisor_evaluation' => 'array',
        'portfolio_essays' => 'array',
    ];
}
