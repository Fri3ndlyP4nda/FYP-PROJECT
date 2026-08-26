<?php

namespace App\Models;

use App\Domain\Apel\ApelStage;
use App\Domain\Apel\StageMachine;
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

        /*
         | The canonical workflow position. Everything below it that looks like
         | state — status, review_stage, credit_status, payment_status — is now
         | derived from this by StageMachine and kept only so the older print
         | and export views keep rendering. New code reads stage().
         */
        'stage',
        'stage_entered_at',
        'stage_history',

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

        /*
         | True when two evaluators reported and disagreed. The old code
         | silently resolved a split panel as "not recommended", so a single
         | dissenting evaluator sank the candidate and nobody was told a
         | disagreement had happened at all.
         */
        'panel_split',

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
        'stage_entered_at' => 'datetime',
        'stage_history' => 'array',
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

    /*
    |--------------------------------------------------------------------------
    | Reading the workflow
    |--------------------------------------------------------------------------
    |
    | Views used to decide what to show by matching substrings against the
    | status string — str_contains($status, 'approved') painted "Advisor
    | Approved" in the same green as a final award, and the progress tracker
    | listed stages no code ever wrote. Everything the interface needs to know
    | now comes from these four methods.
    |
    */

    public function stage(): ApelStage
    {
        return StageMachine::current($this);
    }

    /** The application type, normalised to one of the two known values. */
    public function type(): string
    {
        return StageMachine::type($this);
    }

    /** Ordered progress nodes for the stage rail. */
    public function rail(): array
    {
        return $this->stage()->rail($this->type());
    }

    public function stageLabel(): string
    {
        return $this->stage()->label($this->type());
    }

    public function stageExplanation(): string
    {
        return $this->stage()->studentExplanation($this->type());
    }

    public function isApelC(): bool
    {
        return $this->type() === ApelStage::APEL_C;
    }

    /** A short, stable reference students and staff can quote. */
    public function reference(): string
    {
        $year = $this->submission_date?->format('Y') ?? date('Y');
        $shortId = strtoupper(substr((string) $this->_id, -6));

        return sprintf('APL-%s-%s', $year, $shortId);
    }
}
