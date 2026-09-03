<?php

namespace Tests\Feature;

use App\Domain\Apel\ApelStage;
use Tests\FeatureTestCase;
use Tests\MakesApelRecords;

/**
 * Temporary harness: render the rebuilt dashboards against every stage.
 */
class DashboardRenderTest extends FeatureTestCase
{
    use MakesApelRecords;

    public function test_student_dashboard_renders_at_every_stage(): void
    {
        $student = $this->makeStudent();

        foreach (ApelStage::cases() as $stage) {
            foreach (['APEL A', 'APEL C'] as $type) {
                $this->makeApplication($student, [
                    'application_type' => $type,
                    'stage' => $stage->value,
                    'program_applied' => 'Bachelor of Engineering',
                ]);
            }
        }

        $response = $this->actingAs($student)->get(route('student.dashboard'));

        $response->assertOk();
        $response->assertSee('Your applications', false);
        // The rail must actually render, not just the shell.
        $response->assertSee('spine-node', false);
    }

    public function test_student_dashboard_renders_with_no_applications(): void
    {
        $student = $this->makeStudent();

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('You have not applied yet.', false);
    }

    public function test_evaluator_dashboard_renders_at_every_stage(): void
    {
        $evaluator = $this->makeUser('evaluator');
        $student = $this->makeStudent();

        foreach (ApelStage::cases() as $stage) {
            $this->makeApplication($student, [
                'application_type' => 'APEL C',
                'stage' => $stage->value,
                'status' => 'Submitted',
                'evaluator_id' => (string) $evaluator->_id,
                'program_applied' => 'Bachelor of Engineering',
            ]);
        }

        $this->actingAs($evaluator)
            ->get(route('evaluator.dashboard'))
            ->assertOk()
            ->assertSee('Assigned to you', false);
    }

    /** The second evaluator saw a dashboard of zeroes before this. */
    public function test_evaluator_dashboard_includes_cases_where_they_are_the_second_evaluator(): void
    {
        $first = $this->makeUser('evaluator');
        $second = $this->makeUser('evaluator');
        $student = $this->makeStudent();

        $this->makeApplication($student, [
            'application_type' => 'APEL A',
            'stage' => ApelStage::UNDER_REVIEW->value,
            'status' => 'Submitted',
            'evaluator_id' => (string) $first->_id,
            'evaluator_2_id' => (string) $second->_id,
            'program_applied' => 'Bachelor of Engineering',
        ]);

        $this->actingAs($second)
            ->get(route('evaluator.dashboard'))
            ->assertOk()
            ->assertDontSee('Nothing is assigned to you.', false);
    }

    /** An APEL A assignee saw zeroes before this, because every query filtered to APEL C. */
    public function test_evaluator_dashboard_includes_apel_a_cases(): void
    {
        $evaluator = $this->makeUser('evaluator');
        $student = $this->makeStudent();

        $this->makeApplication($student, [
            'application_type' => 'APEL A',
            'stage' => ApelStage::UNDER_REVIEW->value,
            'status' => 'Submitted',
            'evaluator_id' => (string) $evaluator->_id,
            'program_applied' => 'Bachelor of Engineering',
        ]);

        $this->actingAs($evaluator)
            ->get(route('evaluator.dashboard'))
            ->assertOk()
            ->assertDontSee('Nothing is assigned to you.', false);
    }

    /**
     * The index used to classify by substring-matching the legacy status
     * string, and status is written as $stage->label($type). An APEL A
     * rejection carries the label "Not approved", so
     * str_contains('not approved', 'approved') put a turned-down application
     * in the Approved bucket on the candidate's own list.
     */
    public function test_a_rejected_application_is_never_presented_as_approved(): void
    {
        $student = $this->makeStudent();

        $this->makeApplication($student, [
            'application_type' => 'APEL A',
            'stage' => ApelStage::REJECTED->value,
            'program_applied' => 'Bachelor of Engineering',
        ]);

        $response = $this->actingAs($student)->get(route('student.applications.index'));

        $response->assertOk();
        $response->assertSee('Closed', false);
        $response->assertDontSee('Needs you', false);

        // The badge must carry the rejection tone, not a success one.
        $response->assertSee('badge--bad', false);
        $response->assertDontSee('badge--good', false);
    }

    /** Both APEL C outcomes fell through the old test and were counted pending. */
    public function test_apel_c_outcomes_are_grouped_as_closed(): void
    {
        $student = $this->makeStudent();

        foreach ([ApelStage::APPROVED, ApelStage::REJECTED] as $stage) {
            $this->makeApplication($student, [
                'application_type' => 'APEL C',
                'stage' => $stage->value,
                'program_applied' => 'Bachelor of Engineering',
            ]);
        }

        $this->actingAs($student)
            ->get(route('student.applications.index'))
            ->assertOk()
            ->assertSee('Closed', false)
            ->assertDontSee('Moving', false);
    }

    public function test_evaluator_queue_renders_grouped(): void
    {
        $evaluator = $this->makeUser('evaluator');
        $student = $this->makeStudent();

        $this->makeApplication($student, [
            'application_type' => 'APEL A',
            'stage' => ApelStage::UNDER_REVIEW->value,
            'status' => 'Submitted',
            'evaluator_id' => (string) $evaluator->_id,
            'program_applied' => 'Bachelor of Engineering',
        ]);

        $this->actingAs($evaluator)
            ->get(route('evaluator.applications.index'))
            ->assertOk()
            ->assertSee('Assigned to you', false)
            ->assertSee('row-case', false);
    }

    /**
     * Both detail views built their own progress tracker in Blade, keyed on
     * status strings the application stopped writing when the stage machine
     * landed. All 19 stages hit `default => 0`, so every candidate saw step 1
     * regardless of where they were. These render the real rail at every stage.
     */
    public function test_student_detail_views_render_the_real_rail_at_every_stage(): void
    {
        $student = $this->makeStudent();

        foreach ([['APEL A', 'student.apel_a.show'], ['APEL C', 'student.apel_c.show']] as [$type, $route]) {
            foreach (ApelStage::cases() as $stage) {
                $application = $this->makeApplication($student, [
                    'application_type' => $type,
                    'stage' => $stage->value,
                    'program_applied' => 'Bachelor of Engineering',
                ]);

                $response = $this->actingAs($student)->get(route($route, $application->_id));

                $response->assertOk();
                $response->assertSee('Where this sits', false);

                // A terminal stage has no rail node left to be "current", but
                // every stage must still draw the rail itself.
                $response->assertSee('spine-node', false);
            }
        }
    }

    /** A refusal must never be presented in the tone of a success. */
    public function test_a_refused_application_reads_as_refused_on_its_detail_page(): void
    {
        $student = $this->makeStudent();

        $application = $this->makeApplication($student, [
            'application_type' => 'APEL A',
            'stage' => ApelStage::REJECTED->value,
            'program_applied' => 'Bachelor of Engineering',
        ]);

        $this->actingAs($student)
            ->get(route('student.apel_a.show', $application->_id))
            ->assertOk()
            ->assertSee('outcome--bad', false)
            ->assertDontSee('outcome--good', false);
    }

    public function test_grading_queue_groups_by_what_still_needs_marking(): void
    {
        $evaluator = $this->makeUser('evaluator');
        $student = $this->makeStudent();

        $toMark = $this->makeApplication($student, [
            'application_type' => 'APEL C',
            'stage' => ApelStage::SUBMITTED_FOR_GRADING->value,
            'status' => 'Submitted',
            'evaluator_id' => (string) $evaluator->_id,
        ]);
        $this->makeSubmission($toMark);

        $this->actingAs($evaluator)
            ->get(route('evaluator.assessment.grading.index'))
            ->assertOk()
            ->assertSee('Waiting on you', false)
            ->assertSee('To mark', false);
    }

    /**
     * The marking form applies an unusual rule - 5 of 10 on EVERY outcome, so
     * 10/10/10/4 fails - and the old layout led with a running total out of 40
     * and a percentage, the two numbers the rule does not use. The form must
     * state the rule it is actually applying.
     */
    public function test_the_marking_form_states_the_rule_it_applies(): void
    {
        $evaluator = $this->makeUser('evaluator');
        $student = $this->makeStudent();

        $application = $this->makeApplication($student, [
            'application_type' => 'APEL C',
            'stage' => ApelStage::SUBMITTED_FOR_GRADING->value,
            'status' => 'Submitted',
            'evaluator_id' => (string) $evaluator->_id,
        ]);
        $submission = $this->makeSubmission($application);

        $response = $this->actingAs($evaluator)
            ->get(route('evaluator.assessment.grading.show', $submission->_id));

        $response->assertOk();
        $response->assertSee('at least 5 on', false);
        $response->assertSee('every', false);

        // All four outcomes must be markable.
        foreach ([1, 2, 3, 4] as $i) {
            $response->assertSee('name="clo'.$i.'"', false);
        }

        // The literal asterisks from a stray markdown emphasis are gone.
        $response->assertDontSee('**PASS**', false);
    }

    public function test_review_screen_renders_for_both_tracks_at_every_stage(): void
    {
        $evaluator = $this->makeUser('evaluator');
        $student = $this->makeStudent();

        foreach (['APEL A', 'APEL C'] as $type) {
            foreach (ApelStage::cases() as $stage) {
                if ($stage === ApelStage::DRAFT) {
                    continue; // The queue excludes drafts.
                }

                $application = $this->makeApplication($student, [
                    'application_type' => $type,
                    'stage' => $stage->value,
                    'status' => 'Submitted',
                    'evaluator_id' => (string) $evaluator->_id,
                    'program_applied' => 'Bachelor of Engineering',
                ]);

                $this->actingAs($evaluator)
                    ->get(route('evaluator.applications.show', $application->_id))
                    ->assertOk()
                    ->assertSee('Your recommendation', false);
            }
        }
    }

    /**
     * update() validates admission_decision as in:recommended,not_recommended.
     * The old form offered "Pending" and preselected it, so the default
     * submission could only come back as a validation error.
     */
    public function test_the_review_form_offers_only_decisions_the_controller_accepts(): void
    {
        $evaluator = $this->makeUser('evaluator');
        $student = $this->makeStudent();

        $application = $this->makeApplication($student, [
            'application_type' => 'APEL A',
            'stage' => ApelStage::UNDER_REVIEW->value,
            'status' => 'Submitted',
            'evaluator_id' => (string) $evaluator->_id,
        ]);

        $response = $this->actingAs($evaluator)
            ->get(route('evaluator.applications.show', $application->_id));

        $response->assertOk();
        $response->assertSee('value="recommended"', false);
        $response->assertSee('value="not_recommended"', false);
        $response->assertDontSee('value="pending"', false);
    }
}
