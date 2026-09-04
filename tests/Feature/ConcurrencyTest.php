<?php

namespace Tests\Feature;

use App\Domain\Apel\ApelStage;
use App\Domain\Apel\ConcurrentStageChange;
use App\Domain\Apel\StageMachine;
use App\Models\Application;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Tests\FeatureTestCase;
use Tests\MakesApelRecords;

/**
 * What happens when two people act on the same application at once.
 *
 * Nothing in the suite covered this, and it is the failure mode that only
 * appears once more than one person is using the system - which is exactly when
 * nobody is watching for it.
 *
 * A request cannot be threaded here, but the race does not need threads to
 * reproduce: it is a read-check-write, and all that matters is that both actors
 * read before either wrote. Loading the same application into two model
 * instances is a faithful stand-in for two web requests that arrived together,
 * because that is precisely the state those two requests are in.
 */
class ConcurrencyTest extends FeatureTestCase
{
    use MakesApelRecords;

    private function applicationAtPaymentVerified(): Application
    {
        $student = $this->makeStudent();

        return $this->makeApplication($student, [
            'application_type' => 'APEL A',
            'stage' => ApelStage::PAYMENT_VERIFIED->value,
            'status' => ApelStage::PAYMENT_VERIFIED->label('APEL A'),
        ]);
    }

    /**
     * Two officers open the same case, both press the button.
     *
     * Before the guard, both writes landed: the second silently overwrote the
     * first, and because each appended to the copy of stage_history it had read,
     * the first officer's entry vanished from the audit trail entirely.
     */
    public function test_a_second_write_against_a_stale_read_is_refused(): void
    {
        $created = $this->applicationAtPaymentVerified();

        // Two requests, each having loaded the application before either wrote.
        $first = Application::where('_id', $created->_id)->firstOrFail();
        $second = Application::where('_id', $created->_id)->firstOrFail();

        StageMachine::transition($first, ApelStage::EVALUATOR_ASSIGNED);

        $this->expectException(ConcurrentStageChange::class);
        StageMachine::transition($second, ApelStage::EVALUATOR_ASSIGNED);
    }

    /** The first writer's audit entry must survive the second attempt. */
    public function test_the_losing_writer_does_not_erase_the_winners_history(): void
    {
        $created = $this->applicationAtPaymentVerified();

        $first = Application::where('_id', $created->_id)->firstOrFail();
        $second = Application::where('_id', $created->_id)->firstOrFail();

        StageMachine::transition($first, ApelStage::EVALUATOR_ASSIGNED, [], 'first officer');

        try {
            StageMachine::transition($second, ApelStage::EVALUATOR_ASSIGNED, [], 'second officer');
        } catch (ConcurrentStageChange) {
            // Expected.
        }

        $fresh = Application::where('_id', $created->_id)->firstOrFail();
        $notes = collect($fresh->stage_history ?? [])->pluck('note')->filter()->values();

        $this->assertTrue(
            $notes->contains('first officer'),
            'The winning write must remain in the audit trail.',
        );
        $this->assertFalse(
            $notes->contains('second officer'),
            'A refused write must leave no trace of having happened.',
        );
    }

    /**
     * Two different moves from the same starting point - an admin finalising
     * while an evaluator reports. Only one may land.
     */
    public function test_two_different_moves_from_one_stage_cannot_both_land(): void
    {
        $student = $this->makeStudent();
        $created = $this->makeApplication($student, [
            'application_type' => 'APEL A',
            'stage' => ApelStage::AWAITING_DECISION->value,
            'status' => ApelStage::AWAITING_DECISION->label('APEL A'),
        ]);

        $approver = Application::where('_id', $created->_id)->firstOrFail();
        $rejecter = Application::where('_id', $created->_id)->firstOrFail();

        StageMachine::transition($approver, ApelStage::APPROVED);

        $this->expectException(ConcurrentStageChange::class);
        StageMachine::transition($rejecter, ApelStage::REJECTED);
    }

    /** The ordinary single-actor path must be untouched by the guard. */
    public function test_a_normal_sequential_transition_still_works(): void
    {
        $created = $this->applicationAtPaymentVerified();

        $application = StageMachine::transition($created, ApelStage::EVALUATOR_ASSIGNED);
        $this->assertSame(ApelStage::EVALUATOR_ASSIGNED->value, $application->getAttributes()['stage']);

        $application = StageMachine::transition($application, ApelStage::UNDER_REVIEW);
        $this->assertSame(ApelStage::UNDER_REVIEW->value, $application->getAttributes()['stage']);
    }

    /**
     * The loser sees a sentence, not a 500.
     *
     * transition() is called from twenty-one places and only two caught
     * anything, so this is handled centrally in bootstrap/app.php. This drives
     * the registered renderer rather than contriving a race through HTTP:
     * every controller reloads the application before writing, so the real
     * window is the milliseconds inside one request, which a sequential test
     * client cannot reproduce. What is worth pinning is that the handler is
     * wired and produces something a person can act on.
     */
    public function test_the_handler_turns_a_lost_race_into_a_message_on_the_page(): void
    {
        $exception = new ConcurrentStageChange(
            ApelStage::PAYMENT_VERIFIED,
            ApelStage::EVALUATOR_ASSIGNED,
            ApelStage::EVALUATOR_ASSIGNED,
            'APEL A',
        );

        $handler = app(ExceptionHandler::class);
        $response = $handler->render(Request::create('/admin/applications/x/assign', 'POST'), $exception);

        $this->assertSame(302, $response->getStatusCode(), 'It must send the officer back, not to an error page.');
        $this->assertStringContainsString('Reload', $exception->forHumans());
        $this->assertStringContainsString(
            ApelStage::EVALUATOR_ASSIGNED->label('APEL A'),
            $exception->forHumans(),
            'The message should name where the application actually is now.',
        );
    }

    /** A JSON caller gets 409 Conflict, which is what the status code is for. */
    public function test_a_json_caller_gets_a_conflict_status(): void
    {
        $exception = new ConcurrentStageChange(
            ApelStage::AWAITING_DECISION,
            ApelStage::APPROVED,
            ApelStage::REJECTED,
            'APEL C',
        );

        $request = Request::create('/admin/applications/x/finalize', 'POST');
        $request->headers->set('Accept', 'application/json');

        $response = app(ExceptionHandler::class)->render($request, $exception);

        $this->assertSame(409, $response->getStatusCode());
    }
}
