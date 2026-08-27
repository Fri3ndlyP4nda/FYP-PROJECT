<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\AssessmentPaper;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\FeatureTestCase;

/**
 * The authorisation model, asserted end to end.
 *
 * Three separate mechanisms decide who may touch what, and each fails in a
 * different way. This file exists to prove all three, because a hole in any one
 * of them exposes another candidate's record:
 *
 *   1. RoleMiddleware — redirects an anonymous visitor to /login, and aborts
 *      403 for a signed-in user holding the wrong role string.
 *
 *   2. Ownership scoped INTO the query — every student controller looks the
 *      application up with ->where('user_id', Auth::id())->firstOrFail(), and
 *      every evaluator controller with ->where(evaluator_id or evaluator_2_id).
 *      A record belonging to somebody else therefore comes back as 404, not
 *      403: the query simply does not find it. That is the shape asserted
 *      below, and it is deliberate — a 403 would confirm the id exists.
 *
 *   3. Hand-rolled checks — ApplicationController::printPortfolio and
 *      SecureFileController::mayReadApplication sit behind plain 'auth' with no
 *      role middleware and decide for themselves (owner / either assigned
 *      evaluator / admin), aborting 403.
 *
 * The application supports a two-evaluator panel, so every ownership rule has
 * to honour evaluator_2_id as well as evaluator_id. A check that forgot the
 * second half would lock a legitimate evaluator out of their own caseload and
 * log nothing to say so, which is why the evaluator cases below are asserted in
 * both directions — denied AND granted.
 */
class RoleBoundaryTest extends FeatureTestCase
{
    /** Any well-formed ObjectId. The middleware never gets far enough to load it. */
    private const ABSENT_ID = '507f1f77bcf86cd799439011';

    /**
     * Every route behind role:student, as [method, uri].
     *
     * Adding a route to routes/web.php means adding one line here, and the two
     * data-driven tests below cover it for free.
     */
    private const STUDENT_ROUTES = [
        ['GET', '/student/dashboard'],
        ['GET', '/student/applications'],
        ['GET', '/student/applications/create'],
        ['POST', '/student/applications'],
        ['GET', '/student/apel-a/'.self::ABSENT_ID],
        ['GET', '/student/apel-c/'.self::ABSENT_ID],
        ['GET', '/student/applications/'.self::ABSENT_ID.'/assessment'],
        ['POST', '/student/applications/'.self::ABSENT_ID.'/assessment'],
        ['POST', '/student/applications/'.self::ABSENT_ID.'/payment'],
        ['POST', '/student/applications/'.self::ABSENT_ID.'/appeal'],
        ['POST', '/student/applications/'.self::ABSENT_ID.'/upload-portfolio'],
        ['POST', '/student/applications/'.self::ABSENT_ID.'/submit-portfolio'],
        ['GET', '/student/applications/'.self::ABSENT_ID.'/edit'],
        ['PUT', '/student/applications/'.self::ABSENT_ID],
    ];

    /** Every route behind role:evaluator, as [method, uri]. */
    private const EVALUATOR_ROUTES = [
        ['GET', '/evaluator/dashboard'],
        ['GET', '/evaluator/applications'],
        ['GET', '/evaluator/applications/'.self::ABSENT_ID],
        ['POST', '/evaluator/applications/'.self::ABSENT_ID],
        ['GET', '/evaluator/apel-a'],
        ['GET', '/evaluator/assessment-papers'],
        ['GET', '/evaluator/applications/'.self::ABSENT_ID.'/assessment-paper/create'],
        ['POST', '/evaluator/applications/'.self::ABSENT_ID.'/assessment-paper'],
        ['DELETE', '/evaluator/assessment-papers/'.self::ABSENT_ID],
        ['GET', '/evaluator/assessment-submissions'],
        ['GET', '/evaluator/assessment-submissions/'.self::ABSENT_ID],
        ['POST', '/evaluator/assessment-submissions/'.self::ABSENT_ID.'/grade'],
    ];

    /** Every route behind role:admin, as [method, uri]. */
    private const ADMIN_ROUTES = [
        ['GET', '/admin/dashboard'],
        ['GET', '/admin/applications'],
        ['GET', '/admin/apel-a'],
        ['GET', '/admin/applications/'.self::ABSENT_ID.'/assign'],
        ['GET', '/admin/applications/'.self::ABSENT_ID.'/brief'],
        ['POST', '/admin/applications/'.self::ABSENT_ID.'/assign'],
        ['POST', '/admin/applications/'.self::ABSENT_ID.'/advisor-approve'],
        ['POST', '/admin/applications/'.self::ABSENT_ID.'/finalize-apel-a'],
        ['POST', '/admin/applications/'.self::ABSENT_ID.'/finalize-apel-c'],
        ['POST', '/admin/applications/'.self::ABSENT_ID.'/status'],
        ['POST', '/admin/applications/'.self::ABSENT_ID.'/payment'],
        ['GET', '/admin/users'],
        ['GET', '/admin/users/create'],
        ['POST', '/admin/users'],
        ['GET', '/admin/users/'.self::ABSENT_ID.'/edit'],
        ['PUT', '/admin/users/'.self::ABSENT_ID],
        ['GET', '/admin/reports/apel-a'],
        ['GET', '/admin/reports/apel-a/export'],
        ['GET', '/admin/reports/apel-c'],
        ['GET', '/admin/reports/apel-c/export'],
    ];

    /**
     * Routes behind plain 'auth' with no role middleware. They are open to all
     * three roles and decide for themselves who may read what, so they belong
     * in the anonymous-visitor sweep but not the wrong-role one.
     */
    private const AUTH_ONLY_ROUTES = [
        ['GET', '/student/applications/'.self::ABSENT_ID.'/print'],
        ['GET', '/files/application/'.self::ABSENT_ID],
        ['GET', '/files/paper/'.self::ABSENT_ID],
        ['GET', '/files/submission/'.self::ABSENT_ID],
    ];

    /** @return array<string, array<int, array{0: string, 1: string}>> */
    private function roleGatedRoutes(): array
    {
        return [
            'student' => self::STUDENT_ROUTES,
            'evaluator' => self::EVALUATOR_ROUTES,
            'admin' => self::ADMIN_ROUTES,
        ];
    }

    private function assertStatusIs(int $expected, TestResponse $response, string $because): void
    {
        $this->assertSame($expected, $response->getStatusCode(), $because);
    }

    /**
     * A 404 only proves an ownership check if the rightful owner is NOT given
     * one. Some of those pages cannot currently be asserted as 200 because
     * every route that reads an application's stage throws — see
     * test_an_application_can_report_the_stage_it_is_at at the bottom of this
     * file. Until that is fixed, "the owner was found" is the strongest thing
     * these controls can honestly claim; they still fail loudly if the query
     * scope is ever widened or narrowed by mistake.
     */
    private function assertNotTreatedAsAStranger(TestResponse $response, string $because): void
    {
        $this->assertNotSame(404, $response->getStatusCode(), $because);
    }

    /**
     * The stage as it is actually stored, read straight out of the document.
     *
     * $application->stage cannot be used for this — the attribute is shadowed
     * by Application::stage() and reading it throws. See the final test.
     */
    private function storedStage(Application $application): ?string
    {
        return $application->fresh()->getAttributes()['stage'] ?? null;
    }

    // -----------------------------------------------------------------------
    // 1. Nobody gets in without signing in.
    // -----------------------------------------------------------------------

    public function test_every_role_gated_route_sends_an_unauthenticated_visitor_to_the_login_page(): void
    {
        foreach ($this->roleGatedRoutes() as $role => $routes) {
            foreach ($routes as [$method, $uri]) {
                $response = $this->call($method, $uri);

                $this->assertStatusIs(
                    302,
                    $response,
                    "{$method} {$uri} (role:{$role}) answered an anonymous visitor instead of redirecting them.",
                );
                $this->assertSame(
                    route('login'),
                    $response->headers->get('Location'),
                    "{$method} {$uri} (role:{$role}) sent an anonymous visitor somewhere other than the login page.",
                );
            }
        }
    }

    public function test_the_print_and_secure_file_routes_also_turn_an_unauthenticated_visitor_away(): void
    {
        foreach (self::AUTH_ONLY_ROUTES as [$method, $uri]) {
            $response = $this->call($method, $uri);

            $this->assertStatusIs(
                302,
                $response,
                "{$method} {$uri} sits behind 'auth' but answered an anonymous visitor.",
            );
            $this->assertSame(
                route('login'),
                $response->headers->get('Location'),
                "{$method} {$uri} sent an anonymous visitor somewhere other than the login page.",
            );
        }
    }

    // -----------------------------------------------------------------------
    // 2. Signing in as the wrong kind of user is not enough.
    // -----------------------------------------------------------------------

    public function test_every_role_gated_route_refuses_a_signed_in_user_of_the_wrong_role(): void
    {
        $users = [
            'student' => $this->makeStudent(),
            'evaluator' => $this->makeEvaluator(),
            'admin' => $this->makeAdmin(),
        ];

        foreach ($this->roleGatedRoutes() as $requiredRole => $routes) {
            foreach ($users as $role => $user) {
                if ($role === $requiredRole) {
                    continue;
                }

                foreach ($routes as [$method, $uri]) {
                    $response = $this->actingAs($user)->call($method, $uri);

                    $this->assertStatusIs(
                        403,
                        $response,
                        "A {$role} was not refused at {$requiredRole}-only {$method} {$uri}.",
                    );
                }
            }
        }
    }

    // -----------------------------------------------------------------------
    // 3. IDOR — one student reaching into another student's record.
    // -----------------------------------------------------------------------

    public function test_a_student_cannot_open_another_students_apel_a_application(): void
    {
        $intruder = $this->makeStudent();
        $owner = $this->makeStudent();
        $application = $this->makeApplication($owner);

        $this->assertStatusIs(
            404,
            $this->actingAs($intruder)->get("/student/apel-a/{$application->_id}"),
            'One candidate could open another candidate\'s APEL A application.',
        );

        $this->assertStatusIs(
            200,
            $this->actingAs($owner)->get("/student/apel-a/{$application->_id}"),
            'The 404 above is only meaningful if the owner themselves is let through.',
        );
    }

    public function test_a_student_cannot_open_another_students_apel_c_application(): void
    {
        $intruder = $this->makeStudent();
        $owner = $this->makeStudent();
        $application = $this->makeApelC($owner);

        $this->assertStatusIs(
            404,
            $this->actingAs($intruder)->get("/student/apel-c/{$application->_id}"),
            'One candidate could open another candidate\'s APEL C application.',
        );

        $this->assertNotTreatedAsAStranger(
            $this->actingAs($owner)->get("/student/apel-c/{$application->_id}"),
            'The 404 above is only meaningful if the owner themselves is found.',
        );
    }

    public function test_a_student_cannot_open_the_edit_form_of_another_students_draft(): void
    {
        $intruder = $this->makeStudent();
        $owner = $this->makeStudent();
        $draft = $this->makeApplication($owner, ['stage' => 'draft']);

        $this->assertStatusIs(
            404,
            $this->actingAs($intruder)->get("/student/applications/{$draft->_id}/edit"),
            'One candidate could open the edit form for another candidate\'s draft.',
        );

        $this->assertStatusIs(
            200,
            $this->actingAs($owner)->get("/student/applications/{$draft->_id}/edit"),
            'The 404 above must come from the ownership check, not from the draft being unreachable.',
        );
    }

    public function test_a_student_cannot_update_another_students_draft(): void
    {
        $intruder = $this->makeStudent();
        $owner = $this->makeStudent();
        $draft = $this->makeApplication($owner, ['stage' => 'draft']);

        $this->assertStatusIs(
            404,
            $this->actingAs($intruder)->put("/student/applications/{$draft->_id}", [
                'submit_type' => 'draft',
                'application_type' => 'APEL A',
                'program_applied' => 'Overwritten by an intruder',
            ]),
            'One candidate could PUT over another candidate\'s draft.',
        );

        $this->assertSame(
            'Master of Computer Science (ODL)',
            $draft->fresh()->program_applied,
            'The rejected request still altered the record it was not allowed to see.',
        );
    }

    public function test_a_student_cannot_upload_a_payment_receipt_against_another_students_application(): void
    {
        Storage::fake('private');

        $intruder = $this->makeStudent();
        $owner = $this->makeStudent();
        $application = $this->makeApplication($owner, ['stage' => 'payment_due']);

        $this->assertStatusIs(
            404,
            $this->actingAs($intruder)->post("/student/applications/{$application->_id}/payment", [
                'payment_receipt' => UploadedFile::fake()->create('receipt.pdf', 16, 'application/pdf'),
                'payment_remarks' => 'Paid.',
            ]),
            'One candidate could attach a receipt to another candidate\'s application.',
        );

        $this->assertNull(
            $application->fresh()->payment_receipt,
            'The rejected receipt was still written onto the application.',
        );

        $this->assertNotTreatedAsAStranger(
            $this->actingAs($owner)->post("/student/applications/{$application->_id}/payment", [
                'payment_receipt' => UploadedFile::fake()->create('receipt.pdf', 16, 'application/pdf'),
                'payment_remarks' => 'Paid.',
            ]),
            'The owner was treated as a stranger on their own payment route.',
        );
    }

    public function test_a_student_cannot_appeal_another_students_rejection(): void
    {
        $intruder = $this->makeStudent();
        $owner = $this->makeStudent();
        $application = $this->makeApplication($owner, ['stage' => 'rejected']);

        $this->assertStatusIs(
            404,
            $this->actingAs($intruder)->post("/student/applications/{$application->_id}/appeal", [
                'appeal_remarks' => 'Filed against a decision that is not mine.',
            ]),
            'One candidate could lodge an appeal on another candidate\'s rejection.',
        );

        $this->assertSame(
            'rejected',
            $this->storedStage($application),
            'The rejected appeal still moved somebody else\'s application.',
        );
        $this->assertNull(
            $application->fresh()->appeal_remarks,
            'The intruder\'s grounds were written onto somebody else\'s application.',
        );

        $this->assertNotTreatedAsAStranger(
            $this->actingAs($owner)->post("/student/applications/{$application->_id}/appeal", [
                'appeal_remarks' => 'I would like this reconsidered.',
            ]),
            'The owner was treated as a stranger on their own appeal route.',
        );
    }

    public function test_a_student_cannot_open_or_submit_the_assessment_of_another_students_application(): void
    {
        Storage::fake('private');

        $intruder = $this->makeStudent();
        $owner = $this->makeStudent();
        $evaluator = $this->makeEvaluator();

        $application = $this->makeApelC($owner, [
            'stage' => 'assessment_set',
            'evaluator_id' => (string) $evaluator->_id,
        ]);
        $this->makePaper($application, $evaluator);

        $this->assertStatusIs(
            404,
            $this->actingAs($intruder)->get("/student/applications/{$application->_id}/assessment"),
            'One candidate could read the assessment paper set for another candidate.',
        );

        $this->assertStatusIs(
            404,
            $this->actingAs($intruder)->post("/student/applications/{$application->_id}/assessment", [
                'answer_file' => UploadedFile::fake()->create('answer.pdf', 16, 'application/pdf'),
            ]),
            'One candidate could sit another candidate\'s assessment.',
        );

        $this->assertSame(
            'assessment_set',
            $this->storedStage($application),
            'The rejected submission still advanced somebody else\'s application.',
        );

        $this->assertNotTreatedAsAStranger(
            $this->actingAs($owner)->get("/student/applications/{$application->_id}/assessment"),
            'The candidate the paper was set for was treated as a stranger.',
        );
    }

    // -----------------------------------------------------------------------
    // 4. IDOR — one evaluator reaching into another evaluator's caseload.
    // -----------------------------------------------------------------------

    public function test_an_evaluator_cannot_open_an_application_they_are_not_assigned_to(): void
    {
        $assigned = $this->makeEvaluator();
        $stranger = $this->makeEvaluator();

        $application = $this->makeApplication($this->makeStudent(), [
            'stage' => 'evaluator_assigned',
            'evaluator_id' => (string) $assigned->_id,
        ]);

        $this->assertStatusIs(
            404,
            $this->actingAs($stranger)->get("/evaluator/applications/{$application->_id}"),
            'An evaluator could open a case assigned to a colleague.',
        );

        $this->assertNotTreatedAsAStranger(
            $this->actingAs($assigned)->get("/evaluator/applications/{$application->_id}"),
            'The evaluator who actually holds the case was not even found.',
        );
    }

    public function test_an_evaluator_cannot_record_a_review_on_an_application_they_are_not_assigned_to(): void
    {
        $assigned = $this->makeEvaluator();
        $stranger = $this->makeEvaluator();

        $application = $this->makeApplication($this->makeStudent(), [
            'stage' => 'under_review',
            'evaluator_id' => (string) $assigned->_id,
        ]);

        $this->assertStatusIs(
            404,
            $this->actingAs($stranger)->post("/evaluator/applications/{$application->_id}", [
                'admission_decision' => 'not_recommended',
                'evaluator_feedback' => 'A recommendation this evaluator has no standing to make.',
            ]),
            'An evaluator could record a recommendation on a colleague\'s case.',
        );

        $this->assertNull(
            $application->fresh()->evaluator_1_decision,
            'The rejected review was still written onto the application.',
        );
    }

    public function test_an_evaluator_cannot_set_an_assessment_paper_on_an_application_they_are_not_assigned_to(): void
    {
        Storage::fake('private');

        $assigned = $this->makeEvaluator();
        $stranger = $this->makeEvaluator();

        $application = $this->makeApelC($this->makeStudent(), [
            'stage' => 'evaluator_assigned',
            'evaluator_id' => (string) $assigned->_id,
        ]);

        $this->assertStatusIs(
            404,
            $this->actingAs($stranger)->post("/evaluator/applications/{$application->_id}/assessment-paper", [
                'paper_source' => 'upload',
                'title' => 'Paper set by an unassigned evaluator',
                'question_file' => UploadedFile::fake()->create('paper.pdf', 16, 'application/pdf'),
                'submission_deadline' => now()->addDays(7)->format('Y-m-d H:i:s'),
            ]),
            'An evaluator could publish an assessment paper onto a colleague\'s case.',
        );

        $this->assertSame(
            0,
            AssessmentPaper::where('application_id', (string) $application->_id)->count(),
            'The rejected paper was still created against the application.',
        );
        $this->assertSame(
            'evaluator_assigned',
            $this->storedStage($application),
            'The rejected request still advanced somebody else\'s application.',
        );
    }

    public function test_an_evaluator_cannot_read_or_grade_a_submission_from_an_application_they_are_not_assigned_to(): void
    {
        $assigned = $this->makeEvaluator();
        $stranger = $this->makeEvaluator();

        $application = $this->makeApelC($this->makeStudent(), [
            'stage' => 'submitted_for_grading',
            'evaluator_id' => (string) $assigned->_id,
        ]);
        $submission = $this->makeSubmission($application);

        $this->assertStatusIs(
            404,
            $this->actingAs($stranger)->get("/evaluator/assessment-submissions/{$submission->_id}"),
            'An evaluator could read an answer script belonging to a colleague\'s candidate.',
        );

        $this->assertStatusIs(
            404,
            $this->actingAs($stranger)->post("/evaluator/assessment-submissions/{$submission->_id}/grade", [
                'clo1' => 10,
                'clo2' => 10,
                'clo3' => 10,
                'clo4' => 10,
                'grader_feedback' => 'A mark this evaluator has no standing to award.',
            ]),
            'An evaluator could grade an answer script belonging to a colleague\'s candidate.',
        );

        $this->assertNull(
            $submission->fresh()->graded_at,
            'The rejected grade was still written onto the submission.',
        );

        $this->assertStatusIs(
            200,
            $this->actingAs($assigned)->get("/evaluator/assessment-submissions/{$submission->_id}"),
            'The evaluator who actually holds the case was refused the submission.',
        );
    }

    /**
     * Every evaluator-side ownership check has to consider evaluator_2_id as
     * well as evaluator_id. One that forgot the second half would silently deny
     * a legitimate evaluator their own caseload, so the grant is asserted here
     * as carefully as the denials are asserted above.
     */
    public function test_the_second_assigned_evaluator_is_granted_access_and_not_only_the_first(): void
    {
        $first = $this->makeEvaluator();
        $second = $this->makeEvaluator();
        $stranger = $this->makeEvaluator();

        $application = $this->makeApelC($this->makeStudent(), [
            'stage' => 'submitted_for_grading',
            'evaluator_id' => (string) $first->_id,
            'evaluator_2_id' => (string) $second->_id,
        ]);
        $submission = $this->makeSubmission($application);

        $this->assertStatusIs(
            200,
            $this->actingAs($second)->get("/evaluator/assessment-submissions/{$submission->_id}"),
            'The second evaluator was denied a submission they are assigned to grade.',
        );

        $this->assertNotTreatedAsAStranger(
            $this->actingAs($second)->get("/evaluator/applications/{$application->_id}"),
            'The second evaluator was not found on a case they are assigned to.',
        );

        // Both halves of the check must still exclude everybody else.
        $this->assertStatusIs(
            404,
            $this->actingAs($stranger)->get("/evaluator/assessment-submissions/{$submission->_id}"),
            'Widening the check to evaluator_2_id also let an unassigned evaluator in.',
        );
    }

    public function test_the_second_assigned_evaluator_sees_the_case_in_their_own_queue(): void
    {
        $first = $this->makeEvaluator();
        $second = $this->makeEvaluator();
        $stranger = $this->makeEvaluator();

        $application = $this->makeApplication($this->makeStudent(), [
            'stage' => 'under_review',
            'program_applied' => 'Master of Data Engineering',
            'evaluator_id' => (string) $first->_id,
            'evaluator_2_id' => (string) $second->_id,
        ]);

        $this->actingAs($second)->get('/evaluator/applications')
            ->assertOk()
            ->assertSee($application->program_applied);

        $this->actingAs($stranger)->get('/evaluator/applications')
            ->assertOk()
            ->assertDontSee($application->program_applied);
    }

    // -----------------------------------------------------------------------
    // 5. The print route: plain 'auth', its own three-way check.
    // -----------------------------------------------------------------------

    public function test_the_print_route_admits_the_owner_both_assigned_evaluators_and_an_administrator_only(): void
    {
        $owner = $this->makeStudent();
        $firstEvaluator = $this->makeEvaluator();
        $secondEvaluator = $this->makeEvaluator();
        $admin = $this->makeAdmin();

        $otherStudent = $this->makeStudent();
        $unassignedEvaluator = $this->makeEvaluator();

        $application = $this->makeApplication($owner, [
            'evaluator_id' => (string) $firstEvaluator->_id,
            'evaluator_2_id' => (string) $secondEvaluator->_id,
        ]);

        $url = "/student/applications/{$application->_id}/print";

        $permitted = [
            'the owning candidate' => $owner,
            'the first assigned evaluator' => $firstEvaluator,
            'the second assigned evaluator' => $secondEvaluator,
            'an administrator' => $admin,
        ];

        foreach ($permitted as $who => $viewer) {
            $this->assertStatusIs(
                200,
                $this->actingAs($viewer)->get($url),
                "The print route refused {$who}.",
            );
        }

        $refused = [
            'an unrelated candidate' => $otherStudent,
            'an evaluator who is not assigned' => $unassignedEvaluator,
        ];

        foreach ($refused as $who => $viewer) {
            $this->assertStatusIs(
                403,
                $this->actingAs($viewer)->get($url),
                "The print route let {$who} read a portfolio that is not theirs.",
            );
        }
    }

    // -----------------------------------------------------------------------
    // 6. The documents themselves, not just the pages that link to them.
    // -----------------------------------------------------------------------

    public function test_an_uploaded_document_is_readable_only_by_the_candidate_their_evaluators_and_an_administrator(): void
    {
        Storage::fake('private');
        Storage::disk('private')->put('payment_receipts/receipt.pdf', 'receipt bytes');

        $owner = $this->makeStudent();
        $firstEvaluator = $this->makeEvaluator();
        $secondEvaluator = $this->makeEvaluator();
        $admin = $this->makeAdmin();

        $otherStudent = $this->makeStudent();
        $unassignedEvaluator = $this->makeEvaluator();

        $application = $this->makeApplication($owner, [
            'payment_receipt' => 'payment_receipts/receipt.pdf',
            'evaluator_id' => (string) $firstEvaluator->_id,
            'evaluator_2_id' => (string) $secondEvaluator->_id,
        ]);

        $url = route('files.application', [
            'application' => (string) $application->_id,
            'path' => 'payment_receipts/receipt.pdf',
        ]);

        $permitted = [
            'the owning candidate' => $owner,
            'the first assigned evaluator' => $firstEvaluator,
            'the second assigned evaluator' => $secondEvaluator,
            'an administrator' => $admin,
        ];

        foreach ($permitted as $who => $viewer) {
            $this->assertStatusIs(
                200,
                $this->actingAs($viewer)->get($url),
                "The secure file route refused {$who} a document they are entitled to read.",
            );
        }

        $refused = [
            'an unrelated candidate' => $otherStudent,
            'an evaluator who is not assigned' => $unassignedEvaluator,
        ];

        foreach ($refused as $who => $viewer) {
            $this->assertStatusIs(
                403,
                $this->actingAs($viewer)->get($url),
                "The secure file route let {$who} read another candidate's payment receipt.",
            );
        }
    }

    public function test_an_authorised_viewer_cannot_use_their_own_application_to_read_a_file_belonging_to_a_different_one(): void
    {
        Storage::fake('private');
        Storage::disk('private')->put('payment_receipts/mine.pdf', 'mine');
        Storage::disk('private')->put('payment_receipts/theirs.pdf', 'theirs');

        $owner = $this->makeStudent();
        $mine = $this->makeApplication($owner, ['payment_receipt' => 'payment_receipts/mine.pdf']);
        $this->makeApplication($this->makeStudent(), ['payment_receipt' => 'payment_receipts/theirs.pdf']);

        $this->assertStatusIs(
            404,
            $this->actingAs($owner)->get(route('files.application', [
                'application' => (string) $mine->_id,
                'path' => 'payment_receipts/theirs.pdf',
            ])),
            'A candidate read a stranger\'s receipt by passing its path against their own application.',
        );
    }

    public function test_a_student_cannot_read_another_students_answer_script(): void
    {
        Storage::fake('private');
        Storage::disk('private')->put('assessment_answers/answer.pdf', 'answer bytes');

        $owner = $this->makeStudent();
        $otherStudent = $this->makeStudent();
        $evaluator = $this->makeEvaluator();

        $application = $this->makeApelC($owner, [
            'stage' => 'submitted_for_grading',
            'evaluator_id' => (string) $evaluator->_id,
        ]);
        $submission = $this->makeSubmission($application, ['answer_file' => 'assessment_answers/answer.pdf']);

        $url = "/files/submission/{$submission->_id}";

        $this->assertStatusIs(
            403,
            $this->actingAs($otherStudent)->get($url),
            'One candidate could download another candidate\'s answer script.',
        );

        $this->assertStatusIs(
            403,
            $this->actingAs($this->makeEvaluator())->get($url),
            'An unassigned evaluator could download a candidate\'s answer script.',
        );

        $this->assertStatusIs(
            200,
            $this->actingAs($owner)->get($url),
            'The candidate was refused their own answer script.',
        );
        $this->assertStatusIs(
            200,
            $this->actingAs($evaluator)->get($url),
            'The assigned evaluator was refused the answer script they must grade.',
        );
    }

    // -----------------------------------------------------------------------
    // 7. The stage accessor several grants above depend on.
    // -----------------------------------------------------------------------

    /**
     * Regression guard for a defect this suite found.
     *
     * Application::stage() shares a name with the `stage` document field, and
     * mongodb/laravel-mongodb resolves an attribute whose name matches a method
     * as an embedded relation BEFORE consulting $attributes. So
     * $application->stage invoked stage(), which called StageMachine::current(),
     * which read $application->stage again — and the driver rejected the
     * ApelStage return as a relation, raising "Undefined property:
     * App\Models\Application::$stage".
     *
     * Every stage read threw: stage(), stageLabel(), stageExplanation(), rail(),
     * and StageMachine::current()/can()/transition() with them. In practice
     * GET /student/apel-c/{id} and GET /evaluator/applications/{id} answered 500
     * to the people entitled to them, and no candidate could submit a receipt,
     * an appeal or an answer script, because those controllers call
     * StageMachine::can() before writing.
     *
     * Fixed by reading the attribute bag directly in StageMachine::current().
     */
    public function test_an_application_can_report_the_stage_it_is_at(): void
    {
        $application = $this->makeApplication($this->makeStudent(), ['stage' => 'payment_due']);

        $this->assertSame('payment_due', $application->stage()->value);
        $this->assertSame('Payment due', $application->stageLabel());
        $this->assertNotSame('', $application->stageExplanation());
        $this->assertNotEmpty($application->rail());
    }
}
