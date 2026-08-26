<?php

namespace App\Domain\Apel;

/**
 * The canonical stage of an APEL application.
 *
 * This enum replaces the four parallel fields the application used to carry
 * (status, review_stage, credit_status, payment_status). Those four drifted
 * apart in practice: the student progress tracker rendered stages such as
 * 'Under Advisor Review', 'Official Application Submitted' and the
 * credit_status values 'portfolio_failed' and 'appeal_submitted' that no code
 * path ever wrote, so parts of the tracker could never light up no matter what
 * happened to the application.
 *
 * Every stage below is written by real code and read by the interface. There
 * are no decorative stages. If a stage exists here, an application can reach
 * it; if an application can reach a state, it is named here.
 *
 * Legal movement between stages lives in StageMachine — this enum only
 * describes what each stage *is*.
 */
enum ApelStage: string
{
    /** Saved by the student but not yet submitted. Only the student sees it. */
    case DRAFT = 'draft';

    /** Submitted and awaiting the first review by the faculty. */
    case SUBMITTED = 'submitted';

    /** APEL C only: sitting with the academic advisor for a recommendation. */
    case ADVISOR_REVIEW = 'advisor_review';

    /** APEL C only: the advisor recommended the candidate; fees now apply. */
    case ADVISOR_APPROVED = 'advisor_approved';

    /** APEL C only: the advisor did not recommend the candidate. Terminal. */
    case ADVISOR_REJECTED = 'advisor_rejected';

    /** The processing fee is payable and no receipt has been uploaded. */
    case PAYMENT_DUE = 'payment_due';

    /** A receipt is with the academic office awaiting verification. */
    case PAYMENT_SUBMITTED = 'payment_submitted';

    /** The office could not verify the receipt; the student must re-upload. */
    case PAYMENT_REJECTED = 'payment_rejected';

    /** Payment confirmed. The application may now be assigned. */
    case PAYMENT_VERIFIED = 'payment_verified';

    /** One or two evaluators hold the application but have not opened it. */
    case EVALUATOR_ASSIGNED = 'evaluator_assigned';

    /**
     * APEL C only: the assessment exists and the student must act — either an
     * assessment paper has been published or portfolio mode has been opened.
     */
    case ASSESSMENT_SET = 'assessment_set';

    /** APEL C only: the student's answer or portfolio is awaiting grading. */
    case SUBMITTED_FOR_GRADING = 'submitted_for_grading';

    /** An evaluator has opened the application and is working on it. */
    case UNDER_REVIEW = 'under_review';

    /** Two evaluators were assigned and exactly one has reported. */
    case PARTIALLY_REVIEWED = 'partially_reviewed';

    /** Every evaluator has reported. The faculty must now decide. */
    case AWAITING_DECISION = 'awaiting_decision';

    /** APEL A: admission granted. APEL C: credit awarded. Terminal. */
    case APPROVED = 'approved';

    /** The faculty declined the application. Terminal unless appealed. */
    case REJECTED = 'rejected';

    /** The student has appealed a rejection. */
    case APPEAL_SUBMITTED = 'appeal_submitted';

    /** The faculty has reopened the application in response to an appeal. */
    case APPEAL_UNDER_REVIEW = 'appeal_under_review';

    public const APEL_A = 'APEL A';
    public const APEL_C = 'APEL C';

    /**
     * The label shown in the interface. A few stages read differently for the
     * two products — an APEL C submission is a *pre-application*, and an
     * approval awards credit rather than admission — so the type is honoured.
     */
    public function label(?string $type = null): string
    {
        $isC = $type === self::APEL_C;

        return match ($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => $isC ? 'Pre-application submitted' : 'Application submitted',
            self::ADVISOR_REVIEW => 'Advisor review',
            self::ADVISOR_APPROVED => 'Advisor recommended',
            self::ADVISOR_REJECTED => 'Not recommended',
            self::PAYMENT_DUE => 'Payment due',
            self::PAYMENT_SUBMITTED => 'Payment submitted',
            self::PAYMENT_REJECTED => 'Payment not accepted',
            self::PAYMENT_VERIFIED => 'Payment verified',
            self::EVALUATOR_ASSIGNED => $isC ? 'Evaluator assigned' : 'Reviewer assigned',
            self::ASSESSMENT_SET => 'Assessment ready',
            self::SUBMITTED_FOR_GRADING => 'Awaiting grading',
            self::UNDER_REVIEW => $isC ? 'Grading in progress' : 'Review in progress',
            self::PARTIALLY_REVIEWED => 'One review received',
            self::AWAITING_DECISION => $isC ? 'Awaiting credit decision' : 'Awaiting final decision',
            self::APPROVED => $isC ? 'Credit awarded' : 'Admission approved',
            self::REJECTED => $isC ? 'Credit not awarded' : 'Not approved',
            self::APPEAL_SUBMITTED => 'Appeal submitted',
            self::APPEAL_UNDER_REVIEW => 'Appeal under review',
        };
    }

    /**
     * One plain sentence telling the student what this stage actually means.
     * The old interface showed raw internal strings and left them to guess.
     */
    public function studentExplanation(?string $type = null): string
    {
        $isC = $type === self::APEL_C;

        return match ($this) {
            self::DRAFT => 'You have not submitted this application yet. It is visible only to you.',
            self::SUBMITTED => $isC
                ? 'Your pre-application has reached the faculty and is queued for an academic advisor.'
                : 'Your application has reached the faculty and is queued for review.',
            self::ADVISOR_REVIEW => 'An academic advisor is reading your pre-application and deciding whether to recommend you for assessment.',
            self::ADVISOR_APPROVED => 'The advisor recommended you. The processing fee is now payable.',
            self::ADVISOR_REJECTED => 'The advisor did not recommend this application for assessment. No fee is payable.',
            self::PAYMENT_DUE => 'Upload your payment receipt so the faculty can verify it and continue.',
            self::PAYMENT_SUBMITTED => 'The academic office has your receipt and is verifying it.',
            self::PAYMENT_REJECTED => 'The office could not verify your receipt. Please check the remarks and upload it again.',
            self::PAYMENT_VERIFIED => 'Your payment is confirmed. The faculty is assigning your evaluator.',
            self::EVALUATOR_ASSIGNED => $isC
                ? 'An evaluator now holds your application and will set your assessment shortly.'
                : 'A reviewer now holds your application and will begin shortly.',
            self::ASSESSMENT_SET => 'Your assessment is ready. This is your turn to act.',
            self::SUBMITTED_FOR_GRADING => 'Your work is with the evaluator for grading. Nothing is required from you.',
            self::UNDER_REVIEW => $isC
                ? 'The evaluator is grading your submission.'
                : 'The reviewer is assessing your application.',
            self::PARTIALLY_REVIEWED => 'One of your two evaluators has reported. Waiting on the second.',
            self::AWAITING_DECISION => $isC
                ? 'Grading is complete and the faculty is deciding how much credit to award.'
                : 'Review is complete and the faculty is making the final admission decision.',
            self::APPROVED => $isC
                ? 'Credit has been awarded for this course.'
                : 'Your application for admission through APEL A was approved.',
            self::REJECTED => $isC
                ? 'Credit was not awarded on this occasion. You may appeal.'
                : 'Your application was not approved on this occasion. You may appeal.',
            self::APPEAL_SUBMITTED => 'Your appeal has been received and is queued for the academic office.',
            self::APPEAL_UNDER_REVIEW => 'The faculty has reopened your application to consider your appeal.',
        };
    }

    /**
     * Visual weight for badges and rails. Kept deliberately small so status
     * never has to be re-interpreted by each view — the old interface derived
     * colour from str_contains($status, 'approved'), which painted
     * "Advisor Approved" with the same green as a final award.
     */
    public function tone(): string
    {
        return match ($this) {
            self::DRAFT => 'neutral',
            self::APPROVED, self::ADVISOR_APPROVED, self::PAYMENT_VERIFIED => 'good',
            self::REJECTED, self::ADVISOR_REJECTED, self::PAYMENT_REJECTED => 'bad',
            self::PAYMENT_DUE, self::ASSESSMENT_SET => 'attention',
            self::APPEAL_SUBMITTED, self::APPEAL_UNDER_REVIEW => 'appeal',
            default => 'progress',
        };
    }

    /** True when the application has finished and nothing further will happen. */
    public function isTerminal(): bool
    {
        return in_array($this, [self::APPROVED, self::REJECTED, self::ADVISOR_REJECTED], true);
    }

    /** True when the ball is in the student's court. */
    public function awaitsStudent(): bool
    {
        return in_array($this, [
            self::DRAFT,
            self::PAYMENT_DUE,
            self::PAYMENT_REJECTED,
            self::ASSESSMENT_SET,
        ], true);
    }

    /** True when a member of staff owes the application an action. */
    public function awaitsStaff(): bool
    {
        return ! $this->isTerminal() && ! $this->awaitsStudent();
    }

    /**
     * The ordered happy path for a type. This is what the progress rail draws,
     * and it is the only place the shape of the process is written down.
     */
    public static function pipeline(string $type): array
    {
        if ($type === self::APEL_C) {
            return [
                self::SUBMITTED,
                self::ADVISOR_REVIEW,
                self::ADVISOR_APPROVED,
                self::PAYMENT_DUE,
                self::PAYMENT_VERIFIED,
                self::EVALUATOR_ASSIGNED,
                self::ASSESSMENT_SET,
                self::SUBMITTED_FOR_GRADING,
                self::AWAITING_DECISION,
                self::APPROVED,
            ];
        }

        return [
            self::SUBMITTED,
            self::PAYMENT_DUE,
            self::PAYMENT_VERIFIED,
            self::EVALUATOR_ASSIGNED,
            self::UNDER_REVIEW,
            self::AWAITING_DECISION,
            self::APPROVED,
        ];
    }

    /**
     * Where a stage sits on the rail. Transient stages fold onto the pipeline
     * node they belong to — PAYMENT_SUBMITTED is still the payment step,
     * PARTIALLY_REVIEWED is still the review step — so the rail never has to
     * invent a node for them.
     */
    public function railAnchor(string $type): self
    {
        return match ($this) {
            self::DRAFT => self::SUBMITTED,
            self::PAYMENT_SUBMITTED, self::PAYMENT_REJECTED => self::PAYMENT_DUE,
            self::UNDER_REVIEW, self::PARTIALLY_REVIEWED => $type === self::APEL_C
                ? self::SUBMITTED_FOR_GRADING
                : self::UNDER_REVIEW,
            self::REJECTED, self::APPEAL_SUBMITTED, self::APPEAL_UNDER_REVIEW => self::APPROVED,
            self::ADVISOR_REJECTED => self::ADVISOR_APPROVED,
            default => $this,
        };
    }

    /**
     * The rail as the student sees it: every node, each marked done, current or
     * upcoming, with the final node swapped for the real outcome when the
     * application ended badly.
     */
    public function rail(string $type): array
    {
        $pipeline = self::pipeline($type);
        $anchor = $this->railAnchor($type);
        $position = array_search($anchor, $pipeline, true);
        $position = $position === false ? 0 : $position;

        $nodes = [];
        foreach ($pipeline as $index => $stage) {
            $isLast = $index === count($pipeline) - 1;

            // The closing node states the actual outcome rather than always
            // promising approval.
            $display = $stage;
            if ($isLast && in_array($this, [self::REJECTED, self::APPEAL_SUBMITTED, self::APPEAL_UNDER_REVIEW], true)) {
                $display = self::REJECTED;
            }
            if ($stage === self::ADVISOR_APPROVED && $this === self::ADVISOR_REJECTED) {
                $display = self::ADVISOR_REJECTED;
            }

            $nodes[] = [
                'stage' => $display,
                'label' => $display->label($type),
                'state' => match (true) {
                    $index < $position => 'done',
                    $index === $position => $this->isTerminal() ? 'done' : 'current',
                    default => 'upcoming',
                },
                'tone' => $display->tone(),
            ];

            // An application that ended early stops the rail there rather than
            // showing steps it will never take.
            if ($display === self::ADVISOR_REJECTED) {
                break;
            }
        }

        return $nodes;
    }

    /** Percentage along the pipeline, for compact progress bars. */
    public function progress(string $type): int
    {
        $pipeline = self::pipeline($type);
        $position = array_search($this->railAnchor($type), $pipeline, true);

        if ($position === false || count($pipeline) < 2) {
            return 0;
        }

        return (int) round(($position / (count($pipeline) - 1)) * 100);
    }

    /** Tolerant lookup used when reading documents written before this enum. */
    public static function tryParse(mixed $value): ?self
    {
        if ($value instanceof self) {
            return $value;
        }

        return is_string($value) ? self::tryFrom($value) : null;
    }
}
