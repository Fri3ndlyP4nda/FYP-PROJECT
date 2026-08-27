<?php

namespace Tests\Feature;

use App\Http\Controllers\SecureFileController;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\FeatureTestCase;

/**
 * The defect these tests exist to prevent:
 *
 * Uploaded documents — IC scans, payment receipts, portfolios, answer scripts —
 * used to sit on the PUBLIC disk and were linked with asset('storage/...').
 * Every ownership check in the controllers stopped at the page and never reached
 * the document, so anyone holding the URL could read another candidate's
 * personal records. URLs leak through Referer headers, shared screenshots and
 * browser history, so "the link is unguessable" was never a defence.
 *
 * SecureFileController is now the only way in. These tests hold the line on
 * three separate things it has to get right:
 *
 *   1. Authentication — no anonymous read of anything.
 *   2. Authorisation — the candidate, their assigned evaluators, or an admin,
 *      and nobody else, keyed on the application the file hangs off.
 *   3. Path binding — being authorised for application X must not let you name
 *      an arbitrary storage path and have it streamed back to you.
 *
 * Point 3 is the one that is easy to lose in a refactor: the authorisation check
 * and the file read are two separate steps, and a change that keeps the first
 * while loosening the second reopens the whole hole for any logged-in user.
 */
class SecureFileAccessTest extends FeatureTestCase
{
    /** A path that belongs to the application under test, as the controller stores it. */
    private const RECEIPT = 'payment_receipts/receipt-under-test.pdf';

    private const PORTFOLIO = 'portfolios/portfolio-under-test.pdf';

    protected function setUp(): void
    {
        parent::setUp();

        // No real file is touched: the private disk is swapped for a temporary
        // one that is thrown away with the test.
        Storage::fake('private');
    }

    /** Put a readable document on the private disk at $path. */
    private function placeFile(string $path, string $contents = 'CONFIDENTIAL'): void
    {
        Storage::disk('private')->put($path, $contents);
    }

    /*
    |--------------------------------------------------------------------------
    | 1. Nothing is readable without logging in
    |--------------------------------------------------------------------------
    */

    public function test_an_anonymous_visitor_cannot_read_an_application_document(): void
    {
        $student = $this->makeStudent();
        $application = $this->makeApplication($student, ['payment_receipt' => self::RECEIPT]);
        $this->placeFile(self::RECEIPT);

        $response = $this->get(route('files.application', [
            'application' => (string) $application->_id,
            'path' => self::RECEIPT,
        ]));

        $response->assertRedirect('/login');
    }

    public function test_an_anonymous_visitor_cannot_read_an_assessment_paper(): void
    {
        $student = $this->makeStudent();
        $evaluator = $this->makeEvaluator();
        $application = $this->makeApplication($student, ['evaluator_id' => (string) $evaluator->_id]);
        $paper = $this->makePaper($application, $evaluator);
        $this->placeFile($paper->question_file);

        $this->get(route('files.paper', ['paper' => (string) $paper->_id]))
            ->assertRedirect('/login');
    }

    public function test_an_anonymous_visitor_cannot_read_a_submitted_answer_script(): void
    {
        $student = $this->makeStudent();
        $application = $this->makeApplication($student);
        $submission = $this->makeSubmission($application);
        $this->placeFile($submission->answer_file);

        $this->get(route('files.submission', ['submission' => (string) $submission->_id]))
            ->assertRedirect('/login');
    }

    /*
    |--------------------------------------------------------------------------
    | 2. Who may read an application's documents
    |--------------------------------------------------------------------------
    */

    public function test_a_student_can_read_a_document_attached_to_their_own_application(): void
    {
        $student = $this->makeStudent();
        $application = $this->makeApplication($student, ['payment_receipt' => self::RECEIPT]);
        $this->placeFile(self::RECEIPT, 'MY OWN RECEIPT');

        $response = $this->actingAs($student)->get(route('files.application', [
            'application' => (string) $application->_id,
            'path' => self::RECEIPT,
        ]));

        $response->assertOk();
        $this->assertSame('MY OWN RECEIPT', $response->streamedContent());
    }

    public function test_a_student_cannot_read_a_document_attached_to_another_students_application(): void
    {
        $owner = $this->makeStudent();
        $intruder = $this->makeStudent();
        $application = $this->makeApplication($owner, ['payment_receipt' => self::RECEIPT]);
        $this->placeFile(self::RECEIPT);

        // The document exists and the path is exactly right — the only thing
        // standing between the intruder and someone else's receipt is the check.
        $this->actingAs($intruder)
            ->get(route('files.application', [
                'application' => (string) $application->_id,
                'path' => self::RECEIPT,
            ]))
            ->assertForbidden();
    }

    public function test_the_evaluator_assigned_to_an_application_can_read_its_documents(): void
    {
        $student = $this->makeStudent();
        $evaluator = $this->makeEvaluator();
        $application = $this->makeApplication($student, [
            'evaluator_id' => (string) $evaluator->_id,
            'payment_receipt' => self::RECEIPT,
        ]);
        $this->placeFile(self::RECEIPT, 'EVIDENCE FOR REVIEW');

        $response = $this->actingAs($evaluator)->get(route('files.application', [
            'application' => (string) $application->_id,
            'path' => self::RECEIPT,
        ]));

        $response->assertOk();
        $this->assertSame('EVIDENCE FOR REVIEW', $response->streamedContent());
    }

    public function test_the_second_assigned_evaluator_can_also_read_the_documents(): void
    {
        $student = $this->makeStudent();
        $first = $this->makeEvaluator();
        $second = $this->makeEvaluator();
        $application = $this->makeApplication($student, [
            'evaluator_id' => (string) $first->_id,
            'evaluator_2_id' => (string) $second->_id,
            'payment_receipt' => self::RECEIPT,
        ]);
        $this->placeFile(self::RECEIPT);

        $this->actingAs($second)
            ->get(route('files.application', [
                'application' => (string) $application->_id,
                'path' => self::RECEIPT,
            ]))
            ->assertOk();
    }

    public function test_an_evaluator_who_was_never_assigned_cannot_read_the_documents(): void
    {
        $student = $this->makeStudent();
        $assigned = $this->makeEvaluator();
        $stranger = $this->makeEvaluator();
        $application = $this->makeApplication($student, [
            'evaluator_id' => (string) $assigned->_id,
            'payment_receipt' => self::RECEIPT,
        ]);
        $this->placeFile(self::RECEIPT);

        $this->actingAs($stranger)
            ->get(route('files.application', [
                'application' => (string) $application->_id,
                'path' => self::RECEIPT,
            ]))
            ->assertForbidden();
    }

    public function test_an_administrator_can_read_any_applications_documents(): void
    {
        $student = $this->makeStudent();
        $admin = $this->makeAdmin();
        $application = $this->makeApplication($student, ['payment_receipt' => self::RECEIPT]);
        $this->placeFile(self::RECEIPT, 'FOR THE REGISTRY');

        $response = $this->actingAs($admin)->get(route('files.application', [
            'application' => (string) $application->_id,
            'path' => self::RECEIPT,
        ]));

        $response->assertOk();
        $this->assertSame('FOR THE REGISTRY', $response->streamedContent());
    }

    /*
    |--------------------------------------------------------------------------
    | 3. The path must belong to the application it is requested under
    |--------------------------------------------------------------------------
    */

    public function test_a_path_belonging_to_a_different_application_is_rejected_even_for_an_owner(): void
    {
        $victim = $this->makeStudent();
        $victimApplication = $this->makeApplication($victim, ['payment_receipt' => 'payment_receipts/victim.pdf']);
        $this->placeFile('payment_receipts/victim.pdf', 'THE VICTIMS RECEIPT');

        // The attacker is a perfectly legitimate candidate with an application
        // of their own — they simply name someone else's stored path under it.
        $attacker = $this->makeStudent();
        $attackerApplication = $this->makeApplication($attacker, ['payment_receipt' => self::RECEIPT]);
        $this->placeFile(self::RECEIPT);

        $response = $this->actingAs($attacker)->get(route('files.application', [
            'application' => (string) $attackerApplication->_id,
            'path' => 'payment_receipts/victim.pdf',
        ]));

        $response->assertNotFound();
        // A refusal must not leak the document in the error body either.
        $this->assertStringNotContainsString('THE VICTIMS RECEIPT', $response->getContent());
        $this->assertNotNull($victimApplication->_id);
    }

    public function test_an_administrator_is_also_held_to_the_paths_the_application_actually_owns(): void
    {
        $student = $this->makeStudent();
        $admin = $this->makeAdmin();
        $application = $this->makeApplication($student, ['payment_receipt' => self::RECEIPT]);
        $this->placeFile(self::RECEIPT);
        $this->placeFile('payment_receipts/somebody-else.pdf', 'NOT THIS APPLICATIONS FILE');

        // Full read rights over every application must still not degenerate into
        // "name any path on the disk".
        $this->actingAs($admin)
            ->get(route('files.application', [
                'application' => (string) $application->_id,
                'path' => 'payment_receipts/somebody-else.pdf',
            ]))
            ->assertNotFound();
    }

    public function test_a_missing_path_parameter_is_rejected_rather_than_streaming_something(): void
    {
        $student = $this->makeStudent();
        $application = $this->makeApplication($student, ['payment_receipt' => self::RECEIPT]);
        $this->placeFile(self::RECEIPT);

        $this->actingAs($student)
            ->get(route('files.application', ['application' => (string) $application->_id]))
            ->assertNotFound();
    }

    public function test_paths_stored_in_the_nested_evidence_and_portfolio_groups_are_readable(): void
    {
        $student = $this->makeStudent();
        $application = $this->makeApplication($student, [
            // These groups are cast to arrays and are stored both as bare
            // strings and as {path: ...} maps depending on the upload flow.
            'portfolio_file' => [['path' => self::PORTFOLIO, 'name' => 'portfolio.pdf']],
            'supporting_docs' => ['supporting/transcript.pdf'],
        ]);
        $this->placeFile(self::PORTFOLIO, 'PORTFOLIO BODY');
        $this->placeFile('supporting/transcript.pdf', 'TRANSCRIPT BODY');

        $portfolio = $this->actingAs($student)->get(route('files.application', [
            'application' => (string) $application->_id,
            'path' => self::PORTFOLIO,
        ]));
        $portfolio->assertOk();
        $this->assertSame('PORTFOLIO BODY', $portfolio->streamedContent());

        $transcript = $this->actingAs($student)->get(route('files.application', [
            'application' => (string) $application->_id,
            'path' => 'supporting/transcript.pdf',
        ]));
        $transcript->assertOk();
        $this->assertSame('TRANSCRIPT BODY', $transcript->streamedContent());
    }

    /*
    |--------------------------------------------------------------------------
    | 4. Path traversal
    |--------------------------------------------------------------------------
    */

    #[DataProvider('traversalPaths')]
    public function test_a_path_traversal_attempt_is_rejected(string $attempt): void
    {
        $student = $this->makeStudent();
        $application = $this->makeApplication($student, ['payment_receipt' => self::RECEIPT]);
        $this->placeFile(self::RECEIPT);

        $response = $this->actingAs($student)->get(route('files.application', [
            'application' => (string) $application->_id,
            'path' => $attempt,
        ]));

        $this->assertContains(
            $response->getStatusCode(),
            [403, 404],
            "\"{$attempt}\" was answered with {$response->getStatusCode()}; a traversal attempt must be refused.",
        );
    }

    public static function traversalPaths(): array
    {
        return [
            'up to the project root' => ['../../.env'],
            'deep traversal' => ['../../../../../../etc/passwd'],
            'traversal hidden mid-path' => ['payment_receipts/../../.env'],
            'absolute path' => ['/etc/passwd'],
            'traversal onto a real sibling file' => ['payment_receipts/../payment_receipts/receipt-under-test.pdf'],
        ];
    }

    /**
     * belongsToApplication() only compares the requested path against the paths
     * the record holds, so it cannot catch a traversal that is already stored on
     * the record. That leaves stream() as the last line of defence for the case
     * where the stored path itself is attacker-shaped.
     *
     * The containment holds — Flysystem refuses the path and nothing outside the
     * private disk is streamed — but it holds by throwing, and the throw is not
     * caught, so the candidate gets a 500 instead of a 404.
     */
    public function test_a_traversal_stored_on_the_record_itself_still_does_not_escape_the_disk(): void
    {
        $student = $this->makeStudent();
        $application = $this->makeApplication($student, ['payment_receipt' => '../../.env']);

        $response = $this->actingAs($student)->get(route('files.application', [
            'application' => (string) $application->_id,
            'path' => '../../.env',
        ]));

        // The part that actually matters: the file is not handed over.
        $this->assertNotSame(200, $response->getStatusCode(), 'A traversal path must never be streamed.');
        $this->assertStringNotContainsString('APP_KEY', (string) $response->getContent());

        if (! in_array($response->getStatusCode(), [403, 404], true)) {
            $this->markTestIncomplete(
                'BUG: SecureFileController::stream() calls Storage::disk(\'private\')->exists($path) '
                .'without guarding against League\Flysystem\PathTraversalDetected. A stored path of '
                .'"../../.env" is refused by Flysystem — no file escapes the disk — but the exception '
                .'is uncaught, so the request answers 500 instead of 404. belongsToApplication() cannot '
                .'catch this because the path IS one of the application\'s recorded paths. Fix: normalise '
                .'and reject paths containing ".." (and leading "/") in stream(), or wrap the exists() '
                .'call so a FilesystemException becomes abort(404).',
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 5. Assessment papers
    |--------------------------------------------------------------------------
    */

    public function test_a_candidate_can_read_the_active_paper_set_for_their_application(): void
    {
        $student = $this->makeStudent();
        $evaluator = $this->makeEvaluator();
        $application = $this->makeApelC($student, ['evaluator_id' => (string) $evaluator->_id]);
        $paper = $this->makePaper($application, $evaluator, ['status' => 'active']);
        $this->placeFile($paper->question_file, 'QUESTION PAPER');

        $response = $this->actingAs($student)
            ->get(route('files.paper', ['paper' => (string) $paper->_id]));

        $response->assertOk();
        $this->assertSame('QUESTION PAPER', $response->streamedContent());
    }

    public function test_a_candidate_cannot_read_a_paper_that_is_not_active(): void
    {
        $student = $this->makeStudent();
        $evaluator = $this->makeEvaluator();
        $application = $this->makeApelC($student, ['evaluator_id' => (string) $evaluator->_id]);
        $paper = $this->makePaper($application, $evaluator, ['status' => 'draft']);
        $this->placeFile($paper->question_file, 'NOT YET RELEASED');

        // A superseded or unreleased paper stays with staff — releasing it early
        // would hand the candidate the questions before they are meant to sit.
        $this->actingAs($student)
            ->get(route('files.paper', ['paper' => (string) $paper->_id]))
            ->assertForbidden();
    }

    public function test_a_student_cannot_read_a_paper_belonging_to_another_students_application(): void
    {
        $owner = $this->makeStudent();
        $intruder = $this->makeStudent();
        $evaluator = $this->makeEvaluator();
        $application = $this->makeApelC($owner, ['evaluator_id' => (string) $evaluator->_id]);
        $paper = $this->makePaper($application, $evaluator);
        $this->placeFile($paper->question_file);

        $this->actingAs($intruder)
            ->get(route('files.paper', ['paper' => (string) $paper->_id]))
            ->assertForbidden();
    }

    public function test_an_unassigned_evaluator_cannot_read_a_paper(): void
    {
        $student = $this->makeStudent();
        $assigned = $this->makeEvaluator();
        $stranger = $this->makeEvaluator();
        $application = $this->makeApelC($student, ['evaluator_id' => (string) $assigned->_id]);
        $paper = $this->makePaper($application, $assigned);
        $this->placeFile($paper->question_file);

        $this->actingAs($stranger)
            ->get(route('files.paper', ['paper' => (string) $paper->_id]))
            ->assertForbidden();
    }

    public function test_a_staff_reader_may_open_a_paper_the_candidate_is_not_allowed_to_see(): void
    {
        $student = $this->makeStudent();
        $evaluator = $this->makeEvaluator();
        $application = $this->makeApelC($student, ['evaluator_id' => (string) $evaluator->_id]);
        $paper = $this->makePaper($application, $evaluator, ['status' => 'draft']);
        $this->placeFile($paper->question_file, 'DRAFT PAPER');

        $this->actingAs($evaluator)
            ->get(route('files.paper', ['paper' => (string) $paper->_id]))
            ->assertOk();

        $this->actingAs($this->makeAdmin())
            ->get(route('files.paper', ['paper' => (string) $paper->_id]))
            ->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | 6. Answer scripts
    |--------------------------------------------------------------------------
    */

    public function test_a_candidate_can_read_back_their_own_answer_script(): void
    {
        $student = $this->makeStudent();
        $application = $this->makeApelC($student);
        $submission = $this->makeSubmission($application);
        $this->placeFile($submission->answer_file, 'MY ANSWERS');

        $response = $this->actingAs($student)
            ->get(route('files.submission', ['submission' => (string) $submission->_id]));

        $response->assertOk();
        $this->assertSame('MY ANSWERS', $response->streamedContent());
    }

    public function test_a_student_cannot_read_another_candidates_answer_script(): void
    {
        $owner = $this->makeStudent();
        $intruder = $this->makeStudent();
        $application = $this->makeApelC($owner);
        $submission = $this->makeSubmission($application);
        $this->placeFile($submission->answer_file, 'SOMEONE ELSES ANSWERS');

        $this->actingAs($intruder)
            ->get(route('files.submission', ['submission' => (string) $submission->_id]))
            ->assertForbidden();
    }

    public function test_an_unassigned_evaluator_cannot_read_an_answer_script(): void
    {
        $student = $this->makeStudent();
        $assigned = $this->makeEvaluator();
        $stranger = $this->makeEvaluator();
        $application = $this->makeApelC($student, ['evaluator_id' => (string) $assigned->_id]);
        $submission = $this->makeSubmission($application);
        $this->placeFile($submission->answer_file);

        $this->actingAs($stranger)
            ->get(route('files.submission', ['submission' => (string) $submission->_id]))
            ->assertForbidden();

        $this->actingAs($assigned)
            ->get(route('files.submission', ['submission' => (string) $submission->_id]))
            ->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | 7. The link builder, and the caching rules on a served document
    |--------------------------------------------------------------------------
    */

    public function test_the_url_helper_produces_a_link_this_controller_actually_serves(): void
    {
        $student = $this->makeStudent();
        $application = $this->makeApplication($student, ['payment_receipt' => self::RECEIPT]);
        $this->placeFile(self::RECEIPT, 'ROUND TRIP');

        $url = SecureFileController::url($application, self::RECEIPT);

        $this->assertNotNull($url);
        // Nothing should be able to reach for asset('storage/...') again.
        $this->assertStringNotContainsString('/storage/', $url);

        $response = $this->actingAs($student)->get($url);

        $response->assertOk();
        $this->assertSame('ROUND TRIP', $response->streamedContent());
    }

    public function test_the_url_helper_returns_nothing_when_there_is_no_file(): void
    {
        $application = $this->makeApplication($this->makeStudent());

        $this->assertNull(SecureFileController::url($application, null));
        $this->assertNull(SecureFileController::url($application, ''));
    }

    public function test_a_served_document_is_kept_out_of_shared_caches(): void
    {
        $student = $this->makeStudent();
        $application = $this->makeApplication($student, ['payment_receipt' => self::RECEIPT]);
        $this->placeFile(self::RECEIPT);

        $response = $this->actingAs($student)->get(route('files.application', [
            'application' => (string) $application->_id,
            'path' => self::RECEIPT,
        ]));

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString(
            'no-store',
            (string) $response->headers->get('Cache-Control'),
            'A personal record must not be storable by an intermediate cache.',
        );
    }

    public function test_a_record_whose_file_is_missing_from_the_disk_is_a_not_found_not_an_error(): void
    {
        $student = $this->makeStudent();
        $application = $this->makeApplication($student, ['payment_receipt' => self::RECEIPT]);
        // Deliberately no placeFile() — the database row points at nothing.

        $this->actingAs($student)
            ->get(route('files.application', [
                'application' => (string) $application->_id,
                'path' => self::RECEIPT,
            ]))
            ->assertNotFound();
    }
}
