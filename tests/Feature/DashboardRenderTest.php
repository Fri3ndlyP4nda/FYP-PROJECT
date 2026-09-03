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
}
