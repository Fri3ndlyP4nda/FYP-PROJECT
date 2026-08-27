<?php

namespace App\Domain\Apel;

use App\Models\Application;
use Illuminate\Support\Facades\Auth;

/**
 * The one place an application's stage is allowed to change.
 *
 * Every controller that used to write $application->update(['status' => '...'])
 * now calls StageMachine::transition(). That means:
 *
 *   - an illegal move throws instead of silently corrupting the record;
 *   - the audit trail is written once, here, rather than in eleven places;
 *   - the legacy status / review_stage / credit_status / payment_status fields
 *     are *derived* from the stage, so they can no longer contradict it.
 *
 * The old code let payment verification overwrite "Assessment In Progress",
 * let a student appeal a draft, and let an answer submission jump the record
 * straight to "Awaiting Final Decision" before anything was graded. None of
 * those moves are expressible through this class.
 */
class StageMachine
{
    /**
     * Legal moves, per application type. Anything not listed is rejected.
     */
    private const TRANSITIONS = [
        ApelStage::APEL_A => [
            'draft' => ['submitted'],
            'submitted' => ['payment_due'],
            'payment_due' => ['payment_submitted'],
            'payment_submitted' => ['payment_verified', 'payment_rejected'],
            'payment_rejected' => ['payment_submitted'],
            'payment_verified' => ['evaluator_assigned'],
            'evaluator_assigned' => ['under_review', 'evaluator_assigned'],
            'under_review' => ['partially_reviewed', 'awaiting_decision', 'evaluator_assigned'],
            'partially_reviewed' => ['awaiting_decision', 'evaluator_assigned'],
            'awaiting_decision' => ['approved', 'rejected', 'under_review'],
            'approved' => [],
            'rejected' => ['appeal_submitted'],
            'appeal_submitted' => ['appeal_under_review', 'rejected'],
            'appeal_under_review' => ['under_review', 'approved', 'rejected'],
        ],
        ApelStage::APEL_C => [
            'draft' => ['submitted'],
            'submitted' => ['advisor_review'],
            'advisor_review' => ['advisor_approved', 'advisor_rejected'],
            'advisor_approved' => ['payment_due'],
            'advisor_rejected' => [],
            'payment_due' => ['payment_submitted'],
            'payment_submitted' => ['payment_verified', 'payment_rejected'],
            'payment_rejected' => ['payment_submitted'],
            'payment_verified' => ['evaluator_assigned'],
            'evaluator_assigned' => ['assessment_set', 'under_review', 'evaluator_assigned'],
            'assessment_set' => ['submitted_for_grading', 'evaluator_assigned'],
            'submitted_for_grading' => ['under_review', 'partially_reviewed', 'awaiting_decision'],
            'under_review' => ['partially_reviewed', 'awaiting_decision', 'evaluator_assigned'],
            'partially_reviewed' => ['awaiting_decision', 'evaluator_assigned'],
            'awaiting_decision' => ['approved', 'rejected', 'assessment_set'],
            'approved' => [],
            'rejected' => ['appeal_submitted'],
            'appeal_submitted' => ['appeal_under_review', 'rejected'],
            'appeal_under_review' => ['assessment_set', 'approved', 'rejected'],
        ],
    ];

    /** The stage an application is at, tolerating documents written earlier. */
    public static function current(Application $application): ApelStage
    {
        return ApelStage::tryParse(self::rawStage($application))
            ?? self::inferFromLegacyFields($application);
    }

    /**
     * Read the stored `stage` value without going through __get.
     *
     * Application::stage() and the `stage` attribute share a name, and
     * mongodb/laravel-mongodb's DocumentModel::getAttribute() prefers a
     * same-named method over the attribute bag — it treats the match as an
     * embedded relation. So `$application->stage` called Application::stage(),
     * which called back into here, which read `$application->stage` again.
     *
     * The recursion did not merely loop: getRelationValue() rejected the
     * ApelStage return value as a relation, so every stage read on a real model
     * died with "Undefined property: App\Models\Application::$stage". That
     * meant every stage read and every transition threw, across all 16 call
     * sites - the workflow was fully broken on the branch and nothing caught it,
     * because there were no tests over this path when it landed.
     *
     * Reading the attribute bag directly is the narrow fix: it keeps the
     * convenient $application->stage() accessor working for callers, and does
     * not depend on the driver's relation-detection heuristics.
     */
    private static function rawStage(Application $application): mixed
    {
        return $application->getAttributes()['stage'] ?? null;
    }

    public static function type(Application $application): string
    {
        return $application->application_type === ApelStage::APEL_C
            ? ApelStage::APEL_C
            : ApelStage::APEL_A;
    }

    /**
     * Whether a move is legal for a given type, independent of any record.
     * Kept separate from can() so the transition table can be exercised
     * without a database.
     */
    public static function allows(string $type, ApelStage $from, ApelStage $to): bool
    {
        return in_array($to->value, self::TRANSITIONS[$type][$from->value] ?? [], true);
    }

    /** Whether a move is allowed for this application, without attempting it. */
    public static function can(Application $application, ApelStage $to): bool
    {
        return self::allows(self::type($application), self::current($application), $to);
    }

    /** Every stage reachable from here — used to build admin controls. */
    public static function nextStages(Application $application): array
    {
        $type = self::type($application);
        $from = self::current($application);

        return array_values(array_filter(array_map(
            fn (string $value) => ApelStage::tryFrom($value),
            self::TRANSITIONS[$type][$from->value] ?? [],
        )));
    }

    /**
     * Move an application forward.
     *
     * @param  array  $attributes  Domain data to persist in the same write.
     * @param  string|null  $note  One line for the stage history.
     *
     * @throws IllegalStageTransition
     */
    public static function transition(
        Application $application,
        ApelStage $to,
        array $attributes = [],
        ?string $note = null,
    ): Application {
        $type = self::type($application);
        $from = self::current($application);

        if (! self::can($application, $to)) {
            throw new IllegalStageTransition($from, $to, $type);
        }

        $history = $application->stage_history ?? [];
        $history[] = [
            'stage' => $to->value,
            'label' => $to->label($type),
            'at' => now()->toIso8601String(),
            'by_id' => Auth::check() ? (string) Auth::id() : null,
            'by_name' => Auth::check() ? Auth::user()->name : 'System',
            'by_role' => Auth::check() ? Auth::user()->role : 'system',
            'note' => $note,
        ];

        $application->update(array_merge($attributes, [
            'stage' => $to->value,
            'stage_entered_at' => now(),
            'stage_history' => $history,

            // Derived mirrors. Nothing should read these in new code, but the
            // print views and exports still do, and deriving them here is what
            // stops them drifting away from the stage.
            'status' => $to->label($type),
            'payment_status' => self::derivePaymentStatus($to),
            'review_stage' => $to->value,
            'credit_status' => $type === ApelStage::APEL_C ? $to->value : null,
            'status_updated_at' => now(),
        ]));

        return $application->refresh();
    }

    /**
     * Persist domain data without moving the application. Used for actions that
     * record information but do not advance the process — uploading an extra
     * portfolio file, saving evaluator remarks on a partially reviewed case.
     */
    public static function record(Application $application, array $attributes): Application
    {
        $application->update($attributes);

        return $application->refresh();
    }

    /**
     * payment_status used to be written independently and could contradict the
     * stage. It is now a projection of it.
     */
    private static function derivePaymentStatus(ApelStage $stage): ?string
    {
        return match ($stage) {
            ApelStage::DRAFT,
            ApelStage::SUBMITTED,
            ApelStage::ADVISOR_REVIEW,
            ApelStage::ADVISOR_APPROVED => null,
            ApelStage::ADVISOR_REJECTED => 'cancelled',
            ApelStage::PAYMENT_DUE => 'pending',
            ApelStage::PAYMENT_SUBMITTED => 'submitted',
            ApelStage::PAYMENT_REJECTED => 'rejected',
            default => 'verified',
        };
    }

    /**
     * Read an application written before the stage field existed.
     *
     * This exists so the interface never shows a blank stage for historical
     * documents. The backfill command writes a real stage onto every record;
     * this is the safety net for anything it missed.
     */
    private static function inferFromLegacyFields(Application $application): ApelStage
    {
        $status = (string) ($application->status ?? '');
        $type = self::type($application);

        $byStatus = match ($status) {
            'Draft' => ApelStage::DRAFT,
            'Pre-Application Submitted' => $type === ApelStage::APEL_C ? ApelStage::SUBMITTED : ApelStage::SUBMITTED,
            'Under Advisor Review' => ApelStage::ADVISOR_REVIEW,
            'Advisor Approved' => ApelStage::ADVISOR_APPROVED,
            'Advisor Rejected' => ApelStage::ADVISOR_REJECTED,
            'Payment Pending' => ApelStage::PAYMENT_DUE,
            'Payment Submitted' => ApelStage::PAYMENT_SUBMITTED,
            'Payment Verified' => ApelStage::PAYMENT_VERIFIED,
            'Payment Rejected' => ApelStage::PAYMENT_REJECTED,
            'Assessor Assigned', 'Evaluator Assigned' => ApelStage::EVALUATOR_ASSIGNED,
            'Assessment In Progress' => ApelStage::UNDER_REVIEW,
            'Portfolio Submitted' => ApelStage::SUBMITTED_FOR_GRADING,
            'Awaiting Final Decision' => ApelStage::AWAITING_DECISION,
            'Final Approved' => ApelStage::APPROVED,
            'Final Rejected' => ApelStage::REJECTED,
            'Appeal Submitted' => ApelStage::APPEAL_SUBMITTED,
            default => null,
        };

        return $byStatus ?? ApelStage::DRAFT;
    }
}
