<?php

namespace Tests\Unit;

use App\Domain\Apel\ApelStage;
use App\Domain\Apel\StageMachine;
use PHPUnit\Framework\TestCase;

/**
 * The defect these tests exist to prevent:
 *
 * The old progress tracker walked the student through 'Under Advisor Review',
 * 'Official Application Submitted', 'Portfolio Procedure' and the credit_status
 * values 'portfolio_failed' and 'appeal_submitted' — none of which any code
 * path ever wrote. Parts of the tracker could therefore never light up, and the
 * entire "Portfolio Failed → Appeal Available" branch was unreachable.
 *
 * The first test below makes that class of bug impossible to reintroduce: every
 * stage the rail can draw must be a stage the machine can actually reach.
 */
class ApelStageTest extends TestCase
{
    /** Walking the transition table from DRAFT, which stages are reachable? */
    private function reachable(string $type): array
    {
        $seen = [ApelStage::DRAFT->value => ApelStage::DRAFT];
        $queue = [ApelStage::DRAFT];

        while ($queue) {
            $from = array_shift($queue);

            foreach (ApelStage::cases() as $to) {
                if (! StageMachine::allows($type, $from, $to)) {
                    continue;
                }
                if (isset($seen[$to->value])) {
                    continue;
                }
                $seen[$to->value] = $to;
                $queue[] = $to;
            }
        }

        return $seen;
    }

    public function test_every_pipeline_stage_is_actually_reachable(): void
    {
        foreach ([ApelStage::APEL_A, ApelStage::APEL_C] as $type) {
            $reachable = $this->reachable($type);

            foreach (ApelStage::pipeline($type) as $stage) {
                $this->assertArrayHasKey(
                    $stage->value,
                    $reachable,
                    "{$type} shows \"{$stage->label($type)}\" on the progress rail, but no transition can reach it.",
                );
            }
        }
    }

    public function test_every_rail_node_is_reachable_from_every_stage(): void
    {
        foreach ([ApelStage::APEL_A, ApelStage::APEL_C] as $type) {
            $reachable = $this->reachable($type);

            foreach ($reachable as $stage) {
                foreach ($stage->rail($type) as $node) {
                    $this->assertArrayHasKey(
                        $node['stage']->value,
                        $reachable,
                        "The rail drawn at \"{$stage->label($type)}\" includes an unreachable node.",
                    );
                }
            }
        }
    }

    public function test_no_reachable_stage_is_a_dead_end_unless_it_is_terminal(): void
    {
        foreach ([ApelStage::APEL_A, ApelStage::APEL_C] as $type) {
            foreach ($this->reachable($type) as $stage) {
                if ($stage->isTerminal()) {
                    continue;
                }

                $exits = array_filter(
                    ApelStage::cases(),
                    fn (ApelStage $to) => StageMachine::allows($type, $stage, $to),
                );

                $this->assertNotEmpty(
                    $exits,
                    "A {$type} application can reach \"{$stage->label($type)}\" and never leave it.",
                );
            }
        }
    }

    /**
     * Defect 4 in the audit: submitAppeal() checked nothing, so a student could
     * appeal a draft — and it overwrote status, destroying the real stage.
     */
    public function test_an_appeal_is_only_possible_after_a_rejection(): void
    {
        foreach ([ApelStage::APEL_A, ApelStage::APEL_C] as $type) {
            foreach (ApelStage::cases() as $from) {
                if (! StageMachine::allows($type, $from, ApelStage::APPEAL_SUBMITTED)) {
                    continue;
                }

                $this->assertSame(
                    ApelStage::REJECTED,
                    $from,
                    "A {$type} application should only be appealable from a rejection, not from \"{$from->label($type)}\".",
                );
            }
        }
    }

    /**
     * Defect 2: submitting an answer wrote 'Awaiting Final Decision' before a
     * single mark existed, so the admin queue showed ungraded work as ready to
     * decide.
     */
    public function test_apel_c_cannot_reach_a_decision_without_passing_through_grading(): void
    {
        $this->assertFalse(
            StageMachine::allows(ApelStage::APEL_C, ApelStage::ASSESSMENT_SET, ApelStage::AWAITING_DECISION),
            'A submitted answer must be graded before the faculty can decide on it.',
        );

        $this->assertTrue(
            StageMachine::allows(ApelStage::APEL_C, ApelStage::ASSESSMENT_SET, ApelStage::SUBMITTED_FOR_GRADING),
        );
    }

    /** Defect 1: APEL C asked for payment before the advisor had recommended anything. */
    public function test_apel_c_payment_only_opens_after_the_advisor_recommends(): void
    {
        $this->assertFalse(
            StageMachine::allows(ApelStage::APEL_C, ApelStage::SUBMITTED, ApelStage::PAYMENT_DUE),
            'An APEL C candidate must not be asked to pay before the advisor has recommended them.',
        );

        $this->assertTrue(
            StageMachine::allows(ApelStage::APEL_C, ApelStage::ADVISOR_APPROVED, ApelStage::PAYMENT_DUE),
        );

        // APEL A has no advisor step, so it goes straight to the fee.
        $this->assertTrue(
            StageMachine::allows(ApelStage::APEL_A, ApelStage::SUBMITTED, ApelStage::PAYMENT_DUE),
        );
    }

    public function test_an_evaluator_cannot_be_assigned_before_payment_is_verified(): void
    {
        foreach ([ApelStage::APEL_A, ApelStage::APEL_C] as $type) {
            foreach (ApelStage::cases() as $from) {
                if (! StageMachine::allows($type, $from, ApelStage::EVALUATOR_ASSIGNED)) {
                    continue;
                }

                $this->assertContains(
                    $from,
                    [
                        ApelStage::PAYMENT_VERIFIED,
                        ApelStage::EVALUATOR_ASSIGNED,
                        ApelStage::ASSESSMENT_SET,
                        ApelStage::UNDER_REVIEW,
                        ApelStage::PARTIALLY_REVIEWED,
                    ],
                    "\"{$from->label($type)}\" should not lead straight to an evaluator assignment.",
                );
            }
        }
    }

    public function test_terminal_stages_accept_nothing_except_an_appeal(): void
    {
        foreach ([ApelStage::APEL_A, ApelStage::APEL_C] as $type) {
            foreach ([ApelStage::APPROVED, ApelStage::ADVISOR_REJECTED] as $terminal) {
                foreach (ApelStage::cases() as $to) {
                    $this->assertFalse(
                        StageMachine::allows($type, $terminal, $to),
                        "\"{$terminal->label($type)}\" is final and should accept no further move.",
                    );
                }
            }
        }
    }

    /**
     * Defect 8: the student's own "Approved" tab matched any status containing
     * the word "approved", so a merely-recommended pre-application was filed
     * under Approved. Tone is now decided by the stage, never by substring.
     */
    public function test_an_advisor_recommendation_does_not_read_as_a_final_approval(): void
    {
        $this->assertSame('good', ApelStage::ADVISOR_APPROVED->tone());
        $this->assertSame('good', ApelStage::APPROVED->tone());

        $this->assertFalse(ApelStage::ADVISOR_APPROVED->isTerminal());
        $this->assertTrue(ApelStage::APPROVED->isTerminal());

        $this->assertSame('Advisor recommended', ApelStage::ADVISOR_APPROVED->label(ApelStage::APEL_C));
        $this->assertSame('Credit awarded', ApelStage::APPROVED->label(ApelStage::APEL_C));
        $this->assertSame('Admission approved', ApelStage::APPROVED->label(ApelStage::APEL_A));
    }

    public function test_the_rail_marks_exactly_one_current_node_while_in_progress(): void
    {
        foreach ([ApelStage::APEL_A, ApelStage::APEL_C] as $type) {
            foreach ($this->reachable($type) as $stage) {
                $states = array_column($stage->rail($type), 'state');
                $currents = count(array_keys($states, 'current', true));

                if ($stage->isTerminal()) {
                    $this->assertSame(0, $currents, "A completed application should have no current node.");
                    continue;
                }

                $this->assertSame(
                    1,
                    $currents,
                    "\"{$stage->label($type)}\" produced {$currents} current nodes on the rail; expected exactly one.",
                );
            }
        }
    }

    public function test_the_rail_ends_on_the_real_outcome_not_a_promise_of_approval(): void
    {
        $rail = ApelStage::REJECTED->rail(ApelStage::APEL_C);
        $last = end($rail);

        $this->assertSame(ApelStage::REJECTED, $last['stage']);
        $this->assertSame('Credit not awarded', $last['label']);

        $advisorRejected = ApelStage::ADVISOR_REJECTED->rail(ApelStage::APEL_C);
        $lastAdvisor = end($advisorRejected);

        $this->assertSame(ApelStage::ADVISOR_REJECTED, $lastAdvisor['stage']);
        $this->assertCount(
            3,
            $advisorRejected,
            'An application the advisor turned down should stop at that point, not list steps it will never take.',
        );
    }

    public function test_progress_never_exceeds_its_bounds(): void
    {
        foreach ([ApelStage::APEL_A, ApelStage::APEL_C] as $type) {
            foreach (ApelStage::cases() as $stage) {
                $progress = $stage->progress($type);

                $this->assertGreaterThanOrEqual(0, $progress);
                $this->assertLessThanOrEqual(100, $progress);
            }
        }
    }

    public function test_every_stage_has_a_label_and_an_explanation_for_both_types(): void
    {
        foreach ([ApelStage::APEL_A, ApelStage::APEL_C] as $type) {
            foreach (ApelStage::cases() as $stage) {
                $this->assertNotSame('', trim($stage->label($type)));
                $this->assertNotSame('', trim($stage->studentExplanation($type)));
            }
        }
    }

    public function test_a_stage_is_either_the_students_turn_or_staffs_turn_never_both(): void
    {
        foreach (ApelStage::cases() as $stage) {
            $this->assertFalse(
                $stage->awaitsStudent() && $stage->awaitsStaff(),
                "\"{$stage->value}\" claims to be waiting on both the student and staff at once.",
            );
        }
    }
}
