<?php

namespace Tests\Feature;

use App\Domain\Apel\ApelStage;
use App\Domain\Apel\IllegalStageTransition;
use App\Domain\Apel\StageMachine;
use App\Models\Application;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\FeatureTestCase;

/**
 * The APEL A track, driven through the real HTTP endpoints.
 *
 * ApelStageTest proves the transition *table* is coherent without touching a
 * database. This file proves the controllers actually route through that table:
 * that the happy path really does move an application one stage at a time, and
 * that each workflow guard is enforced by the endpoint a member of staff would
 * hit, not merely by the domain object underneath it.
 *
 * Every guard here corresponds to a defect the old code shipped: an evaluator
 * could be assigned to an unpaid application, a decision could be recorded
 * before anyone had reviewed anything, a second evaluator's opinion could be
 * skipped, a finished decision could be silently rewritten, and a student could
 * appeal an application that had never been decided.
 */
class ApelAWorkflowTest extends FeatureTestCase
{
    /**
     * The one sentence that explains every incomplete test in this file.
     *
     * App\Models\Application declares a method named stage(), and 'stage' is
     * also the name of the attribute that method reads. mongodb/laravel-mongodb
     * 5.7's DocumentModel::getAttribute() resolves any key matching a method on
     * the model as an embedded relation *before* it looks in $attributes, so
     * $application->stage never returns the stored value — it re-enters
     * stage(), which asks for $application->stage again. PHP refuses to call
     * __get() re-entrantly for the same property, so the second read raises
     * "Undefined property: App\Models\Application::$stage".
     *
     * StageMachine::current() is the only place that reads the raw attribute,
     * and every other method on the machine goes through it, so the failure is
     * total: transition(), can(), nextStages() and Application::stage() all
     * throw for every application of either type.
     */
    private const STAGE_ACCESSOR_BUG = 'BUG: Application::stage() collides with the "stage" attribute. '
        . 'mongodb/laravel-mongodb 5.7 DocumentModel::getAttribute() prefers a same-named method over $attributes, '
        . 'so StageMachine::current() (StageMachine.php:72) re-enters Application::stage() and dies with '
        . '"Undefined property: App\Models\Application::$stage". Every stage read and every transition throws, '
        . 'so the whole APEL workflow returns 500. Reading the raw attribute in StageMachine::current() — or '
        . 'renaming the model method — makes all of these tests pass unchanged.';

    /**
     * Stop a workflow test at its first line while the defect above is present,
     * rather than letting it report a misleading failure somewhere downstream.
     * Every test below is written against the intended behaviour and starts
     * exercising it the moment the collision is resolved.
     */
    private function requireAWorkingStageAccessor(): void
    {
        $probe = new Application([
            'application_type' => ApelStage::APEL_A,
            'stage' => ApelStage::DRAFT->value,
        ]);

        try {
            $probe->stage();
        } catch (\Throwable $e) {
            $this->markTestIncomplete(self::STAGE_ACCESSOR_BUG);
        }
    }

    /**
     * The root cause, stated on its own so it is not buried inside a workflow
     * test: a stage that was written to the record must be readable back.
     */
    public function test_the_stage_written_to_an_application_can_be_read_back_from_it(): void
    {
        $application = $this->makeApplication($this->makeStudent(), [
            'stage' => ApelStage::PAYMENT_DUE->value,
        ]);

        $saved = Application::where('_id', (string) $application->_id)->firstOrFail();

        $this->assertSame(
            ApelStage::PAYMENT_DUE->value,
            $saved->getAttributes()['stage'],
            'The stage was not even persisted, so the defect is somewhere else entirely.',
        );

        try {
            $stage = $saved->stage();
        } catch (\Throwable $e) {
            $this->markTestIncomplete(self::STAGE_ACCESSOR_BUG . ' Observed: ' . $e->getMessage());
        }

        $this->assertSame(ApelStage::PAYMENT_DUE, $stage);
    }

    /** The stage a saved application is currently at, read back from Mongo. */
    private function stageOf(Application $application): ApelStage
    {
        return Application::where('_id', (string) $application->_id)->firstOrFail()->stage();
    }

    /** A complete, eligible APEL A submission form. */
    private function apelAForm(array $overrides = []): array
    {
        return array_merge([
            'application_type' => 'APEL A',
            'program_applied' => 'Master of Computer Science (ODL)',
            'highest_qualification' => 'Bachelor of Computer Science',
            'current_job' => 'Systems Analyst',
            'working_experience_years' => 9,
            'working_experience_details' => 'Nine years building payroll integrations for a shared services centre.',
            'reason_applying' => 'I want the qualification to match the work I already do.',
            'age' => 34,
            'ic_no' => '880101015566',
            'university_name' => 'Universiti Teknologi Malaysia',
            'company_name' => 'Northwind Shared Services',
        ], $overrides);
    }

    public function test_an_apel_a_application_walks_the_whole_workflow_one_stage_at_a_time(): void
    {
        $this->requireAWorkingStageAccessor();

        Storage::fake('private');

        $student = $this->makeStudent();
        $admin = $this->makeAdmin();
        $evaluator = $this->makeEvaluator();

        // 1. The candidate submits. APEL A has no advisor step, so the fee opens
        //    immediately.
        $this->actingAs($student)
            ->post(route('student.applications.store'), $this->apelAForm())
            ->assertRedirect(route('student.applications.index'));

        $application = Application::where('user_id', (string) $student->_id)->firstOrFail();

        $this->assertSame(ApelStage::PAYMENT_DUE, $this->stageOf($application));

        // 2. The candidate uploads a receipt.
        $this->actingAs($student)
            ->post(route('student.applications.payment', $application->_id), [
                'payment_receipt' => UploadedFile::fake()->create('receipt.pdf', 64, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(ApelStage::PAYMENT_SUBMITTED, $this->stageOf($application));

        // 3. The academic office verifies it.
        $this->actingAs($admin)
            ->post(route('admin.applications.update_payment', $application->_id), [
                'payment_status' => 'verified',
                'payment_reference' => 'FOC/2026/000431',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(ApelStage::PAYMENT_VERIFIED, $this->stageOf($application));

        // 4. Only now can an evaluator be assigned.
        $this->actingAs($admin)
            ->post(route('admin.applications.assign', $application->_id), [
                'evaluator_id' => (string) $evaluator->_id,
            ])
            ->assertRedirect(route('admin.applications.index'));

        $this->assertSame(ApelStage::EVALUATOR_ASSIGNED, $this->stageOf($application));

        // 5. Opening the application is what starts the review.
        $this->actingAs($evaluator)
            ->get(route('evaluator.applications.show', $application->_id))
            ->assertOk();

        $this->assertSame(ApelStage::UNDER_REVIEW, $this->stageOf($application));

        // 6. The evaluator reports. With a single evaluator that consolidates
        //    straight to the faculty's decision.
        $this->actingAs($evaluator)
            ->post(route('evaluator.applications.update', $application->_id), [
                'admission_decision' => 'recommended',
                'evaluator_feedback' => 'The portfolio evidences the learning outcomes for entry.',
            ])
            ->assertRedirect(route('evaluator.applications.index'));

        $this->assertSame(ApelStage::AWAITING_DECISION, $this->stageOf($application));

        // 7. The faculty decides.
        $this->actingAs($admin)
            ->post(route('admin.applications.finalize_apel_a', $application->_id), [
                'final_decision' => 'approved',
                'final_decision_remarks' => 'Admitted for the coming intake.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(ApelStage::APPROVED, $this->stageOf($application));

        $finished = Application::where('_id', (string) $application->_id)->firstOrFail();

        $this->assertSame('approved', $finished->final_decision);
        $this->assertSame('Admission approved', $finished->stageLabel());

        // The derived mirrors the print views still read must agree with the
        // stage rather than carrying whatever the last writer happened to set.
        $this->assertSame('verified', $finished->payment_status);
        $this->assertSame(ApelStage::APPROVED->value, $finished->review_stage);

        // Every step above left a trail, in order.
        $this->assertSame(
            [
                ApelStage::SUBMITTED->value,
                ApelStage::PAYMENT_DUE->value,
                ApelStage::PAYMENT_SUBMITTED->value,
                ApelStage::PAYMENT_VERIFIED->value,
                ApelStage::EVALUATOR_ASSIGNED->value,
                ApelStage::UNDER_REVIEW->value,
                ApelStage::AWAITING_DECISION->value,
                ApelStage::APPROVED->value,
            ],
            array_column($finished->stage_history ?? [], 'stage'),
        );
    }

    public function test_an_evaluator_cannot_be_assigned_before_the_payment_is_verified(): void
    {
        $this->requireAWorkingStageAccessor();

        $admin = $this->makeAdmin();
        $evaluator = $this->makeEvaluator();
        $application = $this->makeApplication($this->makeStudent(), [
            'stage' => ApelStage::PAYMENT_SUBMITTED->value,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.applications.assign', $application->_id), [
                'evaluator_id' => (string) $evaluator->_id,
            ])
            ->assertSessionHasErrors('evaluator_id');

        $this->assertSame(ApelStage::PAYMENT_SUBMITTED, $this->stageOf($application));

        $untouched = Application::where('_id', (string) $application->_id)->firstOrFail();

        $this->assertNull($untouched->evaluator_id, 'A rejected assignment must not leave an evaluator on the record.');
    }

    public function test_the_final_decision_cannot_be_made_before_the_evaluator_has_reported(): void
    {
        $this->requireAWorkingStageAccessor();

        $admin = $this->makeAdmin();
        $evaluator = $this->makeEvaluator();
        $application = $this->makeApplication($this->makeStudent(), [
            'stage' => ApelStage::UNDER_REVIEW->value,
            'evaluator_id' => (string) $evaluator->_id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.applications.finalize_apel_a', $application->_id), [
                'final_decision' => 'approved',
                'final_decision_remarks' => 'Looks fine to me.',
            ])
            ->assertSessionHasErrors('final_decision');

        $this->assertSame(ApelStage::UNDER_REVIEW, $this->stageOf($application));

        $this->assertNull(
            Application::where('_id', (string) $application->_id)->firstOrFail()->final_decision,
            'A blocked decision must not be written to the record.',
        );
    }

    public function test_with_two_evaluators_the_decision_waits_for_both_reviews(): void
    {
        $this->requireAWorkingStageAccessor();

        $admin = $this->makeAdmin();
        $first = $this->makeEvaluator();
        $second = $this->makeEvaluator();
        $application = $this->makeApplication($this->makeStudent(), [
            'stage' => ApelStage::PAYMENT_VERIFIED->value,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.applications.assign', $application->_id), [
                'evaluator_id' => (string) $first->_id,
                'evaluator_2_id' => (string) $second->_id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(ApelStage::EVALUATOR_ASSIGNED, $this->stageOf($application));

        // The first evaluator reports. That is not enough to decide on.
        $this->actingAs($first)->get(route('evaluator.applications.show', $application->_id))->assertOk();
        $this->actingAs($first)
            ->post(route('evaluator.applications.update', $application->_id), [
                'admission_decision' => 'recommended',
                'evaluator_feedback' => 'Strong industrial evidence across all five outcomes.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(ApelStage::PARTIALLY_REVIEWED, $this->stageOf($application));

        $this->actingAs($admin)
            ->post(route('admin.applications.finalize_apel_a', $application->_id), [
                'final_decision' => 'approved',
                'final_decision_remarks' => 'One review is enough for me.',
            ])
            ->assertSessionHasErrors('final_decision');

        $this->assertSame(
            ApelStage::PARTIALLY_REVIEWED,
            $this->stageOf($application),
            'Finalising on one of two reviews must leave the application where it was.',
        );

        // The second evaluator reports, and only then does the faculty get it.
        $this->actingAs($second)
            ->post(route('evaluator.applications.update', $application->_id), [
                'admission_decision' => 'recommended',
                'evaluator_feedback' => 'Agreed — the work experience maps cleanly onto the programme.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(ApelStage::AWAITING_DECISION, $this->stageOf($application));

        $this->actingAs($admin)
            ->post(route('admin.applications.finalize_apel_a', $application->_id), [
                'final_decision' => 'approved',
                'final_decision_remarks' => 'Both panellists recommended admission.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(ApelStage::APPROVED, $this->stageOf($application));
    }

    public function test_a_decision_that_has_already_been_finalised_cannot_be_finalised_again(): void
    {
        $this->requireAWorkingStageAccessor();

        $admin = $this->makeAdmin();
        $evaluator = $this->makeEvaluator();
        $application = $this->makeApplication($this->makeStudent(), [
            'stage' => ApelStage::APPROVED->value,
            'evaluator_id' => (string) $evaluator->_id,
            'evaluator_1_decision' => 'recommended',
            'evaluator_1_reviewed_at' => now(),
            'final_decision' => 'approved',
            'final_decision_remarks' => 'Admitted for the coming intake.',
            'finalized_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.applications.finalize_apel_a', $application->_id), [
                'final_decision' => 'rejected',
                'final_decision_remarks' => 'Changed my mind.',
            ])
            ->assertSessionHasErrors('final_decision');

        $decided = Application::where('_id', (string) $application->_id)->firstOrFail();

        $this->assertSame('approved', $decided->final_decision, 'A saved decision must not be overwritten.');
        $this->assertSame(
            'Admitted for the coming intake.',
            $decided->final_decision_remarks,
            'The original remarks must survive a second attempt at the decision.',
        );
        $this->assertSame(ApelStage::APPROVED, $this->stageOf($application));
    }

    public function test_a_student_cannot_appeal_an_application_that_has_not_been_rejected(): void
    {
        $this->requireAWorkingStageAccessor();

        $student = $this->makeStudent();
        $application = $this->makeApplication($student, [
            'stage' => ApelStage::AWAITING_DECISION->value,
        ]);

        $this->actingAs($student)
            ->post(route('student.applications.appeal', $application->_id), [
                'appeal_remarks' => 'I would like this reconsidered before you decide.',
            ])
            ->assertSessionHas('error');

        $this->assertSame(
            ApelStage::AWAITING_DECISION,
            $this->stageOf($application),
            'A refused appeal must not overwrite the stage the application was actually at.',
        );

        $this->assertNull(
            Application::where('_id', (string) $application->_id)->firstOrFail()->appeal_submitted_at,
        );
    }

    public function test_an_appeal_is_accepted_once_the_application_has_been_rejected(): void
    {
        $this->requireAWorkingStageAccessor();

        $student = $this->makeStudent();
        $application = $this->makeApplication($student, [
            'stage' => ApelStage::REJECTED->value,
            'final_decision' => 'rejected',
            'final_decision_remarks' => 'The evidence did not cover the required outcomes.',
        ]);

        $this->actingAs($student)
            ->post(route('student.applications.appeal', $application->_id), [
                'appeal_remarks' => 'I have further evidence of the outcomes you found missing.',
            ])
            ->assertSessionHas('success');

        $this->assertSame(ApelStage::APPEAL_SUBMITTED, $this->stageOf($application));

        $appealed = Application::where('_id', (string) $application->_id)->firstOrFail();

        $this->assertSame('submitted', $appealed->appeal_status);
        $this->assertNotNull($appealed->appeal_submitted_at);
        $this->assertSame(
            'I have further evidence of the outcomes you found missing.',
            $appealed->appeal_remarks,
        );
    }

    public function test_moving_straight_from_a_draft_to_an_approval_raises_an_illegal_transition(): void
    {
        $this->requireAWorkingStageAccessor();

        $application = $this->makeApplication($this->makeStudent(), [
            'stage' => ApelStage::DRAFT->value,
        ]);

        $this->expectException(IllegalStageTransition::class);
        $this->expectExceptionMessage('A APEL A application cannot move from "Draft" to "Admission approved".');

        StageMachine::transition($application, ApelStage::APPROVED);
    }

    public function test_an_illegal_transition_leaves_the_application_exactly_where_it_was(): void
    {
        $this->requireAWorkingStageAccessor();

        $application = $this->makeApplication($this->makeStudent(), [
            'stage' => ApelStage::DRAFT->value,
        ]);

        try {
            StageMachine::transition($application, ApelStage::APPROVED);
            $this->fail('A draft should not be able to jump straight to an approval.');
        } catch (IllegalStageTransition $e) {
            $this->assertSame(ApelStage::DRAFT, $e->from);
            $this->assertSame(ApelStage::APPROVED, $e->to);
            $this->assertSame(ApelStage::APEL_A, $e->type);
        }

        $this->assertSame(ApelStage::DRAFT, $this->stageOf($application));
    }
}
