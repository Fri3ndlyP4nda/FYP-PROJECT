<?php

use App\Domain\Apel\ApelStage;
use App\Models\Application;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

/**
 * Give every existing application a canonical stage.
 *
 * Until now workflow position was spread across four fields that were written
 * independently — status, review_stage, credit_status and payment_status — and
 * they routinely disagreed. Submitting an answer wrote status "Awaiting Final
 * Decision" while credit_status said "submitted_for_grading"; verifying a
 * payment overwrote whatever stage the application had actually reached.
 *
 * This migration reads all four, decides which one is telling the truth for
 * each document, and writes a single `stage`. Precedence runs from the most
 * specific signal to the least: a recorded final decision beats a grading
 * record, which beats an assignment, which beats a payment state, which beats
 * the free-text status string.
 *
 * The four legacy fields are deliberately left in place. Nothing new reads
 * them, StageMachine rewrites them as derived mirrors on every transition, and
 * keeping them means the print and export views continue to work untouched.
 */
return new class extends Migration
{
    private const CONNECTION = 'mongodb';

    public function up(): void
    {
        Schema::connection(self::CONNECTION)->table('applications', function (Blueprint $collection) {
            $collection->index('stage');
            $collection->index(['application_type', 'stage']);
        });

        Application::query()->chunkById(100, function ($applications) {
            foreach ($applications as $application) {
                $stage = $this->resolveStage($application);

                $application->timestamps = false;
                $application->update([
                    'stage' => $stage->value,
                    'stage_entered_at' => $application->status_updated_at ?? $application->submission_date ?? now(),
                    'stage_history' => $application->stage_history ?: [[
                        'stage' => $stage->value,
                        'label' => $stage->label($application->application_type),
                        'at' => optional($application->status_updated_at ?? $application->submission_date)->toIso8601String(),
                        'by_id' => null,
                        'by_name' => 'System',
                        'by_role' => 'system',
                        'note' => 'Stage reconstructed from legacy status fields.',
                    ]],
                ]);
            }
        });
    }

    public function down(): void
    {
        Application::query()->chunkById(100, function ($applications) {
            foreach ($applications as $application) {
                $application->timestamps = false;
                $application->update([
                    'stage' => null,
                    'stage_entered_at' => null,
                    'stage_history' => null,
                ]);
            }
        });

        Schema::connection(self::CONNECTION)->table('applications', function (Blueprint $collection) {
            $collection->dropIndex('stage_1');
            $collection->dropIndex('application_type_1_stage_1');
        });
    }

    /**
     * Decide the one true stage for a legacy document, most specific signal
     * first.
     */
    private function resolveStage(Application $application): ApelStage
    {
        $isC = $application->application_type === ApelStage::APEL_C;
        $status = (string) ($application->status ?? '');

        if ($status === 'Draft') {
            return ApelStage::DRAFT;
        }

        // An appeal is the newest thing that can have happened to a record.
        if (($application->appeal_status ?? null) === 'submitted') {
            return ApelStage::APPEAL_SUBMITTED;
        }
        if (($application->appeal_status ?? null) === 'under_review') {
            return ApelStage::APPEAL_UNDER_REVIEW;
        }

        // A recorded final decision outranks everything below it.
        $decision = $isC ? $application->credit_decision : $application->final_decision;
        if ($decision === 'approved') {
            return ApelStage::APPROVED;
        }
        if ($decision === 'rejected') {
            return ApelStage::REJECTED;
        }

        if ($isC && ($application->advisor_evaluation['recommendation'] ?? null) === 'NOT recommended') {
            return ApelStage::ADVISOR_REJECTED;
        }

        // Assessment progress, read from the submission rather than the status
        // string, because the status string was the field most often wrong.
        $submission = \App\Models\AssessmentSubmission::where('application_id', (string) $application->_id)->first();

        if ($submission?->graded_at) {
            return ApelStage::AWAITING_DECISION;
        }
        if ($submission && ! empty($submission->answer_file)) {
            return ApelStage::SUBMITTED_FOR_GRADING;
        }
        if (! empty($application->portfolio_essays)) {
            return ApelStage::SUBMITTED_FOR_GRADING;
        }

        if (! $isC && ! empty($application->reviewed_at)) {
            return ApelStage::AWAITING_DECISION;
        }
        if (! $isC && ! empty($application->evaluator_1_reviewed_at)) {
            return empty($application->evaluator_2_id)
                ? ApelStage::AWAITING_DECISION
                : ApelStage::PARTIALLY_REVIEWED;
        }

        if ($isC && \App\Models\AssessmentPaper::where('application_id', (string) $application->_id)->where('status', 'active')->exists()) {
            return ApelStage::ASSESSMENT_SET;
        }
        if ($isC && ($application->assessment_type ?? '') === 'portfolio' && ! empty($application->evaluator_id)) {
            return ApelStage::ASSESSMENT_SET;
        }

        if (! empty($application->evaluator_id)) {
            return $status === 'Assessment In Progress'
                ? ApelStage::UNDER_REVIEW
                : ApelStage::EVALUATOR_ASSIGNED;
        }

        // Payment, which is the last thing that can be true before assignment.
        return match ($application->payment_status ?? null) {
            'verified' => ApelStage::PAYMENT_VERIFIED,
            'submitted' => ApelStage::PAYMENT_SUBMITTED,
            'rejected' => ApelStage::PAYMENT_REJECTED,
            'cancelled' => ApelStage::ADVISOR_REJECTED,
            'pending' => $isC && empty($application->advisor_approved_at)
                ? ApelStage::ADVISOR_REVIEW
                : ApelStage::PAYMENT_DUE,
            default => $isC ? ApelStage::ADVISOR_REVIEW : ApelStage::SUBMITTED,
        };
    }
};
