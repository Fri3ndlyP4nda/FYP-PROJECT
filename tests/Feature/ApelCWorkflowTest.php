<?php

namespace Tests\Feature;

use App\Domain\Apel\ApelStage;
use App\Domain\Apel\StageMachine;
use App\Models\Application;
use App\Models\AssessmentSubmission;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\FeatureTestCase;

/**
 * The APEL C (credit transfer) track, end to end and at its edges.
 *
 * APEL C is the longer of the two products. It carries two steps APEL A does
 * not — an academic advisor who decides whether a fee is payable at all, and a
 * graded assessment whose marks gate the credit decision — and it is those two
 * steps that every defect in this file's history clustered around: a fee that
 * opened before anyone had read the pre-application, a decision that could be
 * taken before a mark existed, and an advisor whose name was thrown away on the
 * way to the database.
 */
class ApelCWorkflowTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
    }

    /*
    |--------------------------------------------------------------------------
    | The whole journey
    |--------------------------------------------------------------------------
    */

    public function test_an_apel_c_application_walks_from_submission_to_an_awarded_credit(): void
    {
        $student = $this->makeStudent();
        $admin = $this->makeAdmin();
        $evaluator = $this->makeEvaluator();

        // 1. The candidate submits the pre-application. APEL C queues for an
        //    advisor rather than opening a fee the way APEL A does.
        $application = $this->submitPreApplication($student);

        $this->assertStage(ApelStage::ADVISOR_REVIEW, $application);
        $this->assertNull(
            $application->payment_status,
            'No fee is payable until an advisor has recommended the candidate.',
        );

        // 2. The advisor recommends, which is what opens the fee.
        $this->actingAs($admin)
            ->post(route('admin.applications.advisor_approve', $application->_id), $this->advisorPayload())
            ->assertRedirect(route('admin.applications.index'));

        $application = $this->reload($application);
        $this->assertStage(ApelStage::PAYMENT_DUE, $application);
        $this->assertContains(
            ApelStage::ADVISOR_APPROVED->value,
            array_column($application->stage_history, 'stage'),
            'The recommendation itself must be recorded in the history, not skipped over on the way to the fee.',
        );

        // 3. The candidate uploads a receipt.
        $this->actingAs($student)->post(route('student.applications.payment', $application->_id), [
            'payment_receipt' => UploadedFile::fake()->create('receipt.pdf', 40, 'application/pdf'),
        ]);

        $this->assertStage(ApelStage::PAYMENT_SUBMITTED, $application = $this->reload($application));

        // 4. The academic office verifies it.
        $this->actingAs($admin)->post(route('admin.applications.update_payment', $application->_id), [
            'payment_status' => 'verified',
            'payment_reference' => 'FOC/2026/0042',
        ]);

        $this->assertStage(ApelStage::PAYMENT_VERIFIED, $application = $this->reload($application));

        // 5. The faculty assigns an evaluator and picks the assessment mode.
        $this->actingAs($admin)->post(route('admin.applications.assign', $application->_id), [
            'evaluator_id' => (string) $evaluator->_id,
            'assessment_type' => 'test',
        ]);

        $application = $this->reload($application);
        $this->assertStage(ApelStage::EVALUATOR_ASSIGNED, $application);
        $this->assertSame((string) $evaluator->_id, $application->evaluator_id);

        // 6. The evaluator publishes the assessment paper. That is what makes
        //    it the candidate's turn.
        $this->actingAs($evaluator)->post(route('evaluator.assessment.papers.store', $application->_id), [
            'paper_source' => 'upload',
            'title' => 'Software Engineering Challenge Test',
            'submission_deadline' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'question_file' => UploadedFile::fake()->create('paper.pdf', 60, 'application/pdf'),
        ])->assertRedirect(route('evaluator.assessment.papers.index'));

        $application = $this->reload($application);
        $this->assertStage(ApelStage::ASSESSMENT_SET, $application);
        $this->assertTrue($application->stage()->awaitsStudent());

        // 7. The candidate hands in an answer. It is awaiting grading, not a
        //    decision — nothing has been marked yet.
        $this->actingAs($student)->post(route('student.assessment.submit', $application->_id), [
            'answer_file' => UploadedFile::fake()->create('answer.pdf', 80, 'application/pdf'),
        ]);

        $this->assertStage(ApelStage::SUBMITTED_FOR_GRADING, $application = $this->reload($application));

        $submission = AssessmentSubmission::where('application_id', (string) $application->_id)->firstOrFail();
        $this->assertNotEmpty($submission->answer_file);
        $this->assertNull($submission->graded_at);

        // 8. The evaluator grades it.
        $this->actingAs($evaluator)->post(
            route('evaluator.assessment.grading.grade', $submission->_id),
            $this->cloScores(9, 8, 7, 8) + ['grader_feedback' => 'Strong evidence across all four outcomes.'],
        );

        $application = $this->reload($application);
        $this->assertStage(ApelStage::AWAITING_DECISION, $application);

        $submission->refresh();
        $this->assertSame('pass', $submission->result);
        $this->assertSame(80.0, (float) $submission->score, '32 of 40 CLO marks is 80%.');
        $this->assertNotNull($submission->graded_at);

        // 9. The faculty awards the credit.
        $this->actingAs($admin)->post(route('admin.applications.finalize_apel_c', $application->_id), [
            'credit_decision' => 'approved',
            'credit_remarks' => 'Credit awarded for MECS0013.',
        ])->assertRedirect(route('admin.applications.assign.form', $application->_id));

        $application = $this->reload($application);
        $this->assertStage(ApelStage::APPROVED, $application);
        $this->assertSame('approved', $application->credit_decision);
        $this->assertSame(3, (int) $application->credit_hours_approved, 'MECS0013 carries three credit hours.');
        $this->assertTrue($application->stage()->isTerminal());
    }

    /*
    |--------------------------------------------------------------------------
    | The advisor step
    |--------------------------------------------------------------------------
    */

    /**
     * The regression guard for the defect this test file exists for:
     * advisor_name, advisor_approved_at and mode_of_assessment were absent from
     * Application::$fillable, so StageMachine wrote them into an update() that
     * silently dropped all three. The request succeeded, the stage moved, and
     * the record could no longer say who had recommended the candidate or how
     * they were to be assessed.
     *
     * Reading them back out of the database — not off the in-memory model the
     * request happened to leave behind — is the only assertion that catches it.
     */
    public function test_the_advisors_identity_and_chosen_mode_survive_the_write(): void
    {
        $application = $this->submitPreApplication($this->makeStudent());

        $this->actingAs($this->makeAdmin())->post(
            route('admin.applications.advisor_approve', $application->_id),
            $this->advisorPayload([
                'advisor_name' => 'Dr Nurul Hidayah',
                'mode_of_assessment' => 'portfolio',
            ]),
        );

        $persisted = Application::where('_id', (string) $application->_id)->firstOrFail();

        $this->assertSame('Dr Nurul Hidayah', $persisted->advisor_name);
        $this->assertSame('portfolio', $persisted->mode_of_assessment);
        $this->assertNotNull(
            $persisted->advisor_approved_at,
            'The record cannot say when the advisor decided, so the decision is unattributable.',
        );
        $this->assertSame('Recommended', $persisted->advisor_evaluation['recommendation'] ?? null);
    }

    public function test_an_advisor_rejection_is_terminal_and_no_fee_ever_becomes_payable(): void
    {
        $student = $this->makeStudent();
        $application = $this->submitPreApplication($student);

        $this->actingAs($this->makeAdmin())->post(
            route('admin.applications.advisor_approve', $application->_id),
            $this->advisorPayload(['recommendation_status' => 'NOT recommended']),
        );

        $application = $this->reload($application);
        $this->assertStage(ApelStage::ADVISOR_REJECTED, $application);
        $this->assertTrue($application->stage()->isTerminal());
        $this->assertFalse(
            StageMachine::can($application, ApelStage::PAYMENT_DUE),
            'A candidate the advisor turned down must never be asked for money.',
        );

        // And the candidate cannot force the fee open by uploading a receipt.
        $this->actingAs($student)
            ->from(route('student.applications.index'))
            ->post(route('student.applications.payment', $application->_id), [
                'payment_receipt' => UploadedFile::fake()->create('receipt.pdf', 40, 'application/pdf'),
            ])
            ->assertSessionHas('error');

        $application = $this->reload($application);
        $this->assertStage(ApelStage::ADVISOR_REJECTED, $application);
        $this->assertNull($application->payment_receipt);
    }

    /*
    |--------------------------------------------------------------------------
    | The assessment
    |--------------------------------------------------------------------------
    */

    public function test_a_student_cannot_submit_an_answer_once_the_deadline_has_passed(): void
    {
        $student = $this->makeStudent();
        $evaluator = $this->makeEvaluator();

        $application = $this->makeApelC($student, [
            'stage' => ApelStage::ASSESSMENT_SET->value,
            'assessment_type' => 'test',
            'evaluator_id' => (string) $evaluator->_id,
        ]);

        $this->makePaper($application, $evaluator, [
            'submission_deadline' => now()->subDay(),
        ]);

        $this->actingAs($student)
            ->post(route('student.assessment.submit', $application->_id), [
                'answer_file' => UploadedFile::fake()->create('answer.pdf', 80, 'application/pdf'),
            ])
            ->assertRedirect(route('student.assessment.show', $application->_id))
            ->assertSessionHasErrors('error');

        $this->assertSame(
            0,
            AssessmentSubmission::where('application_id', (string) $application->_id)->count(),
            'A late answer must not reach the evaluator\'s grading queue.',
        );
        $this->assertStage(ApelStage::ASSESSMENT_SET, $this->reload($application));
    }

    public function test_an_evaluator_cannot_grade_the_same_submission_twice(): void
    {
        $student = $this->makeStudent();
        $evaluator = $this->makeEvaluator();

        [$application, $submission] = $this->awaitingGrading($student, $evaluator);

        $this->actingAs($evaluator)->post(
            route('evaluator.assessment.grading.grade', $submission->_id),
            $this->cloScores(7, 7, 7, 7),
        );

        $submission->refresh();
        $this->assertSame(70.0, (float) $submission->score);
        $firstGradedAt = $submission->graded_at;
        $this->assertNotNull($firstGradedAt);
        $this->assertStage(ApelStage::AWAITING_DECISION, $this->reload($application));

        // A second attempt — a double-submitted form, a back button — must be
        // refused rather than quietly overwriting the mark that was recorded.
        $this->actingAs($evaluator)
            ->from(route('evaluator.assessment.grading.show', $submission->_id))
            ->post(
                route('evaluator.assessment.grading.grade', $submission->_id),
                $this->cloScores(10, 10, 10, 10),
            )
            ->assertSessionHas('error');

        $submission->refresh();
        $this->assertSame(70.0, (float) $submission->score, 'The original mark was overwritten by a re-grade.');
        $this->assertEquals($firstGradedAt, $submission->graded_at);
        $this->assertStage(ApelStage::AWAITING_DECISION, $this->reload($application));
    }

    /**
     * The pass rule is a floor on every outcome, not a total: half of each of
     * the four CLOs. A candidate can therefore score a comfortable-looking 80%
     * overall and still fail, because one outcome was not met.
     */
    public function test_a_pass_requires_half_marks_on_every_clo_not_merely_a_good_total(): void
    {
        $student = $this->makeStudent();
        $evaluator = $this->makeEvaluator();

        [$application, $submission] = $this->awaitingGrading($student, $evaluator);

        $this->actingAs($evaluator)->post(
            route('evaluator.assessment.grading.grade', $submission->_id),
            $this->cloScores(10, 10, 10, 2),
        );

        $submission->refresh();
        $this->assertSame(80.0, (float) $submission->score, '32 of 40 marks is still 80% overall.');
        $this->assertSame(
            'fail',
            $submission->result,
            'CLO4 scored 2 of 10, which is below the half-marks floor that every outcome must clear.',
        );

        // The boundary itself passes: exactly half on every outcome is enough.
        [$otherApplication, $otherSubmission] = $this->awaitingGrading($this->makeStudent(), $evaluator);

        $this->actingAs($evaluator)->post(
            route('evaluator.assessment.grading.grade', $otherSubmission->_id),
            $this->cloScores(5, 5, 5, 5),
        );

        $otherSubmission->refresh();
        $this->assertSame(50.0, (float) $otherSubmission->score);
        $this->assertSame('pass', $otherSubmission->result);

        $this->assertStage(ApelStage::AWAITING_DECISION, $this->reload($application));
        $this->assertStage(ApelStage::AWAITING_DECISION, $this->reload($otherApplication));
    }

    /*
    |--------------------------------------------------------------------------
    | Two evaluators
    |--------------------------------------------------------------------------
    */

    public function test_a_consolidated_result_waits_for_both_evaluators_and_averages_their_scores(): void
    {
        $student = $this->makeStudent();
        $first = $this->makeEvaluator();
        $second = $this->makeEvaluator();

        [$application, $submission] = $this->awaitingGrading($student, $first, $second);

        // 32/40 = 80%.
        $this->actingAs($first)->post(
            route('evaluator.assessment.grading.grade', $submission->_id),
            $this->cloScores(8, 8, 8, 8) + ['grader_feedback' => 'Comprehensive.'],
        );

        $submission->refresh();
        $this->assertSame(80.0, (float) $submission->evaluator_1_score);
        $this->assertNull($submission->score, 'One evaluator alone must not produce a consolidated score.');
        $this->assertSame('pending', $submission->result);
        $this->assertNull($submission->graded_at);
        $this->assertStage(
            ApelStage::PARTIALLY_REVIEWED,
            $this->reload($application),
            'One report in of two is not a completed grading.',
        );

        // 24/40 = 60%.
        $this->actingAs($second)->post(
            route('evaluator.assessment.grading.grade', $submission->_id),
            $this->cloScores(6, 6, 6, 6) + ['grader_feedback' => 'Adequate.'],
        );

        $submission->refresh();
        $this->assertSame(80.0, (float) $submission->evaluator_1_score);
        $this->assertSame(60.0, (float) $submission->evaluator_2_score);
        $this->assertSame(70.0, (float) $submission->score, 'The consolidated score is the mean of the two.');
        $this->assertSame('pass', $submission->result);
        $this->assertSame('graded', $submission->status);
        $this->assertStage(ApelStage::AWAITING_DECISION, $this->reload($application));
    }

    public function test_a_consolidated_result_fails_when_either_evaluator_failed_the_candidate(): void
    {
        $student = $this->makeStudent();
        $first = $this->makeEvaluator();
        $second = $this->makeEvaluator();

        [$application, $submission] = $this->awaitingGrading($student, $first, $second);

        // Both evaluators award 80% overall, but the second found one outcome
        // unmet — so the panel has not agreed the candidate passed.
        $this->actingAs($first)->post(
            route('evaluator.assessment.grading.grade', $submission->_id),
            $this->cloScores(8, 8, 8, 8),
        );

        $this->actingAs($second)->post(
            route('evaluator.assessment.grading.grade', $submission->_id),
            $this->cloScores(10, 10, 10, 2),
        );

        $submission->refresh();
        $this->assertSame('pass', $submission->evaluator_1_result);
        $this->assertSame('fail', $submission->evaluator_2_result);
        $this->assertSame(80.0, (float) $submission->score);
        $this->assertSame(
            'fail',
            $submission->result,
            'A high average must not launder one evaluator\'s failed outcome into a pass.',
        );
        $this->assertStage(ApelStage::AWAITING_DECISION, $this->reload($application));
    }

    /*
    |--------------------------------------------------------------------------
    | The credit decision
    |--------------------------------------------------------------------------
    */

    public function test_the_credit_decision_cannot_be_finalised_before_grading_is_complete(): void
    {
        $student = $this->makeStudent();
        $evaluator = $this->makeEvaluator();

        $application = $this->makeApelC($student, [
            'stage' => ApelStage::AWAITING_DECISION->value,
            'assessment_type' => 'test',
            'evaluator_id' => (string) $evaluator->_id,
        ]);

        // Handed in, but nobody has marked it.
        $this->makeSubmission($application, ['graded_at' => null, 'result' => 'pending']);

        $this->actingAs($this->makeAdmin())
            ->from(route('admin.applications.assign.form', $application->_id))
            ->post(route('admin.applications.finalize_apel_c', $application->_id), [
                'credit_decision' => 'approved',
                'credit_remarks' => 'Looks fine to me.',
            ])
            ->assertSessionHasErrors('credit_decision');

        $application = $this->reload($application);
        $this->assertStage(ApelStage::AWAITING_DECISION, $application);
        $this->assertNull($application->credit_decision, 'Credit was awarded against an unmarked script.');
    }

    public function test_credit_cannot_be_approved_when_the_grading_outcome_was_a_fail(): void
    {
        $student = $this->makeStudent();
        $evaluator = $this->makeEvaluator();
        $admin = $this->makeAdmin();

        $application = $this->makeApelC($student, [
            'stage' => ApelStage::AWAITING_DECISION->value,
            'assessment_type' => 'test',
            'evaluator_id' => (string) $evaluator->_id,
        ]);

        $this->makeSubmission($application, [
            'status' => 'graded',
            'score' => 42.5,
            'result' => 'fail',
            'graded_at' => now(),
            'graded_by' => (string) $evaluator->_id,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.applications.assign.form', $application->_id))
            ->post(route('admin.applications.finalize_apel_c', $application->_id), [
                'credit_decision' => 'approved',
                'credit_remarks' => 'Awarding anyway.',
            ])
            ->assertSessionHas('error');

        $application = $this->reload($application);
        $this->assertStage(ApelStage::AWAITING_DECISION, $application);
        $this->assertNull($application->credit_decision);
        $this->assertNull($application->credit_hours_approved);

        // The decision is not frozen, only the approval: the faculty can still
        // record the outcome the grading actually supports.
        $this->actingAs($admin)->post(route('admin.applications.finalize_apel_c', $application->_id), [
            'credit_decision' => 'rejected',
            'credit_remarks' => 'The assessment was not passed.',
        ]);

        $application = $this->reload($application);
        $this->assertStage(ApelStage::REJECTED, $application);
        $this->assertSame('rejected', $application->credit_decision);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /** Submit a genuine APEL C pre-application through the student's own route. */
    private function submitPreApplication(User $student): Application
    {
        $course = Course::create([
            'course_code' => 'MECS0013',
            'course_name' => 'Software Engineering',
            'faculty' => 'Faculty of Computing',
            'status' => 'active',
        ]);

        $this->actingAs($student)->post(route('student.applications.store'), [
            'application_type' => 'APEL C',
            'course_id' => (string) $course->_id,
            'target_semester' => 'Semester 1',
            'pre_app_data' => [
                'personal_particulars' => [
                    'name' => $student->name,
                    'highest_qualification' => 'Bachelor',
                ],
                'experiential_learning' => [
                    ['employer' => 'Acme Sdn Bhd', 'time_from' => '2015-01-01', 'time_to' => 'current'],
                ],
                'formal_learning' => [
                    ['title_of_certification' => 'Certified Scrum Master', 'year_awarded' => (string) (date('Y') - 1)],
                ],
            ],
            'self_assessment' => ['clo1' => 'Yes', 'clo2' => 'Yes'],
        ])->assertRedirect(route('student.applications.index'));

        return Application::where('user_id', (string) $student->_id)->firstOrFail();
    }

    private function advisorPayload(array $overrides = []): array
    {
        return array_merge([
            'advisor_name' => 'Dr Advisor',
            'advisor_evaluation' => ['clo1' => 4, 'clo2' => 4, 'clo3' => 3, 'clo4' => 4],
            'recommendation_status' => 'Recommended',
            'mode_of_assessment' => 'test',
            'advisor_remarks' => 'Solid experiential evidence.',
        ], $overrides);
    }

    private function cloScores(int $one, int $two, int $three, int $four): array
    {
        return ['clo1' => $one, 'clo2' => $two, 'clo3' => $three, 'clo4' => $four];
    }

    /**
     * An APEL C application sitting at "awaiting grading" with a real answer
     * script on it, assigned to one or two evaluators.
     *
     * @return array{0: Application, 1: AssessmentSubmission}
     */
    private function awaitingGrading(User $student, User $first, ?User $second = null): array
    {
        $application = $this->makeApelC($student, [
            'stage' => ApelStage::SUBMITTED_FOR_GRADING->value,
            'assessment_type' => 'test',
            'evaluator_id' => (string) $first->_id,
            'evaluator_2_id' => $second ? (string) $second->_id : null,
            'assigned_at' => now(),
        ]);

        $paper = $this->makePaper($application, $first);

        $submission = $this->makeSubmission($application, [
            'assessment_paper_id' => (string) $paper->_id,
            'result' => 'pending',
        ]);

        return [$application, $submission];
    }

    /** Re-read the record from MongoDB rather than trusting an in-memory copy. */
    private function reload(Application $application): Application
    {
        return Application::where('_id', (string) $application->_id)->firstOrFail();
    }

    private function assertStage(ApelStage $expected, Application $application, string $message = ''): void
    {
        $this->assertSame(
            $expected->value,
            $application->stage()->value,
            $message !== '' ? $message : "Expected the application to be at \"{$expected->label(ApelStage::APEL_C)}\".",
        );
    }

    /**
     * The portfolio track had no way in.
     *
     * uploadPortfolio() and submitPortfolio() exist, are validated and are
     * routed - and no view in this project's history ever linked to either. A
     * candidate whose advisor recommended portfolio assessment had nowhere to
     * provide one, so that half of APEL C dead-ended at the recommendation.
     */
    public function test_a_candidate_on_the_portfolio_track_is_offered_a_way_to_submit_one(): void
    {
        $student = $this->makeStudent();

        $application = $this->makeApplication($student, [
            'application_type' => 'APEL C',
            'stage' => ApelStage::ASSESSMENT_SET->value,
            'status' => ApelStage::ASSESSMENT_SET->label('APEL C'),
            'assessment_type' => 'portfolio',
        ]);

        $response = $this->actingAs($student)->get(route('student.apel_c.show', $application->_id));

        $response->assertOk();
        $response->assertSee(route('student.applications.upload_portfolio', $application->_id), false);
        $response->assertSee(route('student.applications.submit_portfolio', $application->_id), false);
        $response->assertSee('portfolio_essays[essay1]', false);

        // And it must not claim nothing is needed while it is still their move.
        $response->assertDontSee('Nothing further is needed from you', false);
    }

    /** Once submitted, the forms go and the page says who holds it. */
    public function test_a_submitted_portfolio_stops_offering_the_form(): void
    {
        $student = $this->makeStudent();

        $application = $this->makeApplication($student, [
            'application_type' => 'APEL C',
            'stage' => ApelStage::SUBMITTED_FOR_GRADING->value,
            'status' => ApelStage::SUBMITTED_FOR_GRADING->label('APEL C'),
            'assessment_type' => 'portfolio',
        ]);

        $this->actingAs($student)
            ->get(route('student.apel_c.show', $application->_id))
            ->assertOk()
            ->assertSee('Nothing further is needed from you', false)
            ->assertDontSee('portfolio_essays[essay1]', false);
    }
}
