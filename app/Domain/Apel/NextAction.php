<?php

namespace App\Domain\Apel;

use App\Models\Application;
use App\Models\AssessmentPaper;
use App\Models\User;

/**
 * "What do I have to do about this application, right now?"
 *
 * Every screen in the rebuilt interface leads with the answer to that question.
 * The old interface showed the student an internal status string and left them
 * to work out whether the ball was in their court — the commonest complaint
 * about a workflow system, and the reason applicants email the office.
 *
 * The answer depends on both the stage and who is looking, so it is computed
 * here once rather than re-derived by each Blade template.
 */
class NextAction
{
    /**
     * @return array{tone:string,title:string,body:string,cta:?array,deadline:?\Illuminate\Support\Carbon,waiting_on:?string}|null
     */
    public static function for(Application $application, ?User $viewer): ?array
    {
        if (! $viewer) {
            return null;
        }

        return match ($viewer->role) {
            'student' => self::forStudent($application),
            'evaluator' => self::forEvaluator($application, $viewer),
            'admin' => self::forAdmin($application),
            default => null,
        };
    }

    private static function forStudent(Application $application): ?array
    {
        $stage = $application->stage();
        $id = (string) $application->_id;
        $isC = $application->isApelC();
        $detailRoute = $isC ? 'student.apel_c.show' : 'student.apel_a.show';

        return match ($stage) {
            ApelStage::DRAFT => self::act(
                'attention',
                'Finish and submit this application',
                'This application has not been submitted yet. Nothing happens until you send it to the faculty.',
                'Continue editing',
                'student.applications.edit',
                $id,
            ),

            ApelStage::PAYMENT_DUE => self::act(
                'attention',
                'Upload your payment receipt',
                $isC
                    ? 'Your advisor recommended you for assessment. Pay the processing fee and upload the receipt so the faculty can assign your evaluator.'
                    : 'Pay the processing fee and upload the receipt so the faculty can assign your reviewer.',
                'Upload receipt',
                $detailRoute,
                $id,
            ),

            ApelStage::PAYMENT_REJECTED => self::act(
                'bad',
                'Your receipt was not accepted',
                trim('The academic office could not verify the receipt you uploaded. ' . ($application->payment_remarks ?: 'Please check the details and upload it again.')),
                'Upload a new receipt',
                $detailRoute,
                $id,
            ),

            ApelStage::ASSESSMENT_SET => self::assessmentAction($application),

            ApelStage::REJECTED => self::act(
                'bad',
                'You may appeal this decision',
                trim(($isC ? 'Credit was not awarded. ' : 'Your application was not approved. ')
                    . ($application->final_decision_remarks ?: $application->credit_remarks ?: '')
                    . ' If you believe the outcome should be reconsidered, you can submit an appeal.'),
                'Submit an appeal',
                $detailRoute,
                $id,
            ),

            default => self::waiting($application),
        };
    }

    private static function assessmentAction(Application $application): array
    {
        $id = (string) $application->_id;
        $isPortfolio = ($application->assessment_type ?? '') === 'portfolio';

        $paper = AssessmentPaper::where('application_id', $id)
            ->where('status', 'active')
            ->first();

        if ($isPortfolio) {
            return self::act(
                'attention',
                'Submit your portfolio',
                'Your evaluator has opened portfolio assessment for this course. Complete the four portfolio essays and attach your supporting documents.',
                'Open portfolio submission',
                'student.apel_c.show',
                $id,
            );
        }

        return self::act(
            'attention',
            'Sit your assessment',
            $paper?->title
                ? "Your assessment paper \"{$paper->title}\" has been published. Download it, complete your answer and upload it before the deadline."
                : 'Your assessment paper has been published. Download it, complete your answer and upload it before the deadline.',
            'Open assessment',
            'student.assessment.show',
            $id,
            $paper?->submission_deadline,
        );
    }

    private static function waiting(Application $application): ?array
    {
        $stage = $application->stage();

        if ($stage->isTerminal() && $stage !== ApelStage::REJECTED) {
            return null;
        }

        return [
            'tone' => $stage->tone(),
            'title' => 'Nothing is required from you',
            'body' => $stage->studentExplanation($application->type()),
            'cta' => null,
            'deadline' => null,
            'waiting_on' => match ($stage) {
                ApelStage::SUBMITTED, ApelStage::ADVISOR_REVIEW => 'the academic advisor',
                ApelStage::PAYMENT_SUBMITTED => 'the academic office',
                ApelStage::AWAITING_DECISION, ApelStage::APPEAL_SUBMITTED, ApelStage::APPEAL_UNDER_REVIEW => 'the faculty',
                default => 'your evaluator',
            },
        ];
    }

    private static function forEvaluator(Application $application, User $viewer): ?array
    {
        $stage = $application->stage();
        $id = (string) $application->_id;
        $isC = $application->isApelC();

        $isFirst = (string) $application->evaluator_id === (string) $viewer->_id;
        $alreadyReported = $isFirst
            ? ! empty($application->evaluator_1_reviewed_at)
            : ! empty($application->evaluator_2_reviewed_at);

        if ($alreadyReported && in_array($stage, [ApelStage::PARTIALLY_REVIEWED, ApelStage::UNDER_REVIEW], true)) {
            return [
                'tone' => 'progress',
                'title' => 'Your review is in',
                'body' => 'You have submitted your assessment. This application is waiting on the second evaluator.',
                'cta' => null,
                'deadline' => null,
                'waiting_on' => 'the second evaluator',
            ];
        }

        return match (true) {
            $stage === ApelStage::EVALUATOR_ASSIGNED && $isC => self::act(
                'attention',
                'Set this candidate\'s assessment',
                'Publish an assessment paper for this course, or confirm portfolio assessment, so the candidate can begin.',
                'Set assessment',
                'evaluator.assessment.papers.create',
                $id,
            ),

            $stage === ApelStage::EVALUATOR_ASSIGNED => self::act(
                'attention',
                'Begin your review',
                'This application has been assigned to you and has not been opened yet.',
                'Open application',
                'evaluator.applications.show',
                $id,
            ),

            $stage === ApelStage::SUBMITTED_FOR_GRADING => self::act(
                'attention',
                'Grade this submission',
                'The candidate has submitted their work. Score it against each course learning outcome.',
                'Open for grading',
                'evaluator.applications.show',
                $id,
            ),

            in_array($stage, [ApelStage::UNDER_REVIEW, ApelStage::PARTIALLY_REVIEWED], true) => self::act(
                'attention',
                $isC ? 'Complete your grading' : 'Submit your recommendation',
                $isC
                    ? 'Score the candidate against each course learning outcome and record your feedback.'
                    : 'Record whether you recommend this candidate for admission, and why.',
                'Continue',
                'evaluator.applications.show',
                $id,
            ),

            $stage === ApelStage::ASSESSMENT_SET => [
                'tone' => 'progress',
                'title' => 'Waiting on the candidate',
                'body' => 'The assessment is published. Nothing is required from you until the candidate submits.',
                'cta' => null,
                'deadline' => null,
                'waiting_on' => 'the candidate',
            ],

            default => null,
        };
    }

    private static function forAdmin(Application $application): ?array
    {
        $stage = $application->stage();
        $id = (string) $application->_id;
        $isC = $application->isApelC();

        return match ($stage) {
            ApelStage::SUBMITTED => $isC
                ? self::act(
                    'attention',
                    'Send this to an academic advisor',
                    'This pre-application is waiting to be put in front of an advisor for a recommendation.',
                    'Open pre-application',
                    'admin.applications.assign.form',
                    $id,
                )
                : self::act(
                    'attention',
                    'Open the fee for this application',
                    'The candidate has applied. Confirm the application is in order so the processing fee becomes payable.',
                    'Review application',
                    'admin.applications.assign.form',
                    $id,
                ),

            ApelStage::ADVISOR_REVIEW => self::act(
                'attention',
                'Record the advisor\'s recommendation',
                'An advisor has this pre-application. Record their decision and the assessment mode they recommend.',
                'Record decision',
                'admin.applications.assign.form',
                $id,
            ),

            ApelStage::PAYMENT_SUBMITTED => self::act(
                'attention',
                'Verify this payment',
                'The candidate has uploaded a receipt. Check it against the faculty ledger and verify or reject it.',
                'Verify payment',
                'admin.applications.assign.form',
                $id,
            ),

            ApelStage::PAYMENT_VERIFIED => self::act(
                'attention',
                'Assign an evaluator',
                'Payment is confirmed. Assign one or two evaluators so assessment can begin.',
                'Assign evaluator',
                'admin.applications.assign.form',
                $id,
            ),

            ApelStage::AWAITING_DECISION => self::act(
                'attention',
                $isC ? 'Make the credit decision' : 'Make the final decision',
                $isC
                    ? 'Grading is complete. Decide how much credit to award for this course.'
                    : 'Every evaluator has reported. Make the final admission decision.',
                'Decide',
                'admin.applications.assign.form',
                $id,
            ),

            ApelStage::APPEAL_SUBMITTED => self::act(
                'appeal',
                'Consider this appeal',
                trim('The candidate has appealed. ' . ($application->appeal_remarks ?: '')),
                'Review appeal',
                'admin.applications.assign.form',
                $id,
            ),

            default => null,
        };
    }

    private static function act(
        string $tone,
        string $title,
        string $body,
        string $ctaLabel,
        string $route,
        string $id,
        mixed $deadline = null,
    ): array {
        return [
            'tone' => $tone,
            'title' => $title,
            'body' => $body,
            'cta' => ['label' => $ctaLabel, 'route' => $route, 'params' => $id],
            'deadline' => $deadline,
            'waiting_on' => null,
        ];
    }
}
