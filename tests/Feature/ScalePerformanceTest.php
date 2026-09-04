<?php

namespace Tests\Feature;

use App\Domain\Apel\ApelStage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\FeatureTestCase;
use Tests\MakesApelRecords;

/**
 * Does a screen's cost grow with the size of the data behind it?
 *
 * A page that issues one query per row looks fine against the twenty
 * applications in a demo database and falls over at two thousand. Nothing in
 * the suite measured that, so a query inside a Blade loop was invisible until
 * it was in production.
 *
 * Each test renders the same screen against two data sets an order of magnitude
 * apart and asserts the query count barely moves. The absolute number does not
 * matter; the slope does.
 */
class ScalePerformanceTest extends FeatureTestCase
{
    use MakesApelRecords;

    /** @return array{queries:int,ms:float} */
    private function measure(User $actor, string $url): array
    {
        DB::connection('mongodb')->flushQueryLog();
        DB::connection('mongodb')->enableQueryLog();

        $start = microtime(true);
        $this->actingAs($actor)->get($url)->assertOk();
        $ms = (microtime(true) - $start) * 1000;

        $queries = count(DB::connection('mongodb')->getQueryLog());
        DB::connection('mongodb')->disableQueryLog();

        return ['queries' => $queries, 'ms' => $ms];
    }

    private function seedApplications(User $student, int $count, array $attributes = []): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->makeApplication($student, array_merge([
                'application_type' => $i % 2 === 0 ? 'APEL A' : 'APEL C',
                'stage' => ApelStage::UNDER_REVIEW->value,
                'status' => 'Submitted',
                'program_applied' => 'Bachelor of Engineering',
            ], $attributes));
        }
    }

    /**
     * The candidate's own list. Their case count is naturally small, but the
     * screen must not query per row regardless.
     */
    public function test_the_student_list_does_not_query_per_application(): void
    {
        $student = $this->makeStudent();

        $this->seedApplications($student, 3);
        $small = $this->measure($student, route('student.applications.index'));

        $this->seedApplications($student, 30);
        $large = $this->measure($student, route('student.applications.index'));

        $this->assertLessThanOrEqual(
            $small['queries'] + 2,
            $large['queries'],
            'Ten times the applications must not mean ten times the queries. '.
            "3 applications: {$small['queries']} queries. 33: {$large['queries']}.",
        );
    }

    /** The evaluator's queue is the screen they live in. */
    public function test_the_evaluator_queue_does_not_query_per_application(): void
    {
        $evaluator = $this->makeUser('evaluator');
        $student = $this->makeStudent();

        $this->seedApplications($student, 3, ['evaluator_id' => (string) $evaluator->_id]);
        $small = $this->measure($evaluator, route('evaluator.applications.index'));

        $this->seedApplications($student, 30, ['evaluator_id' => (string) $evaluator->_id]);
        $large = $this->measure($evaluator, route('evaluator.applications.index'));

        $this->assertLessThanOrEqual(
            $small['queries'] + 2,
            $large['queries'],
            "3 applications: {$small['queries']} queries. 33: {$large['queries']}.",
        );
    }

    /**
     * The registry queue is the one that sees every application in the
     * institution, so it is the first to feel scale.
     */
    public function test_the_registry_queue_does_not_query_per_application(): void
    {
        $admin = $this->makeUser('admin');
        $student = $this->makeStudent();

        $this->seedApplications($student, 3);
        $small = $this->measure($admin, route('admin.applications.index'));

        $this->seedApplications($student, 30);
        $large = $this->measure($admin, route('admin.applications.index'));

        $this->assertLessThanOrEqual(
            $small['queries'] + 2,
            $large['queries'],
            "3 applications: {$small['queries']} queries. 33: {$large['queries']}.",
        );
    }

    /** The registry overview reads metrics across everything. */
    public function test_the_registry_overview_does_not_query_per_application(): void
    {
        $admin = $this->makeUser('admin');
        $student = $this->makeStudent();

        $this->seedApplications($student, 3);
        $small = $this->measure($admin, route('admin.dashboard'));

        $this->seedApplications($student, 30);
        $large = $this->measure($admin, route('admin.dashboard'));

        $this->assertLessThanOrEqual(
            $small['queries'] + 2,
            $large['queries'],
            "3 applications: {$small['queries']} queries. 33: {$large['queries']}.",
        );
    }

    /** The account list resolves a live workload for every evaluator. */
    public function test_the_account_list_does_not_query_per_user(): void
    {
        $admin = $this->makeUser('admin');

        for ($i = 0; $i < 3; $i++) {
            $this->makeUser('evaluator');
        }
        $small = $this->measure($admin, route('admin.users.index'));

        for ($i = 0; $i < 30; $i++) {
            $this->makeUser('evaluator');
        }
        $large = $this->measure($admin, route('admin.users.index'));

        $this->assertLessThanOrEqual(
            $small['queries'] + 2,
            $large['queries'],
            "4 users: {$small['queries']} queries. 34: {$large['queries']}.",
        );
    }

    /**
     * The printed report renders every application in the institution, so it
     * is the screen that feels a per-row query first. Both report views used
     * to resolve a User - and for APEL C a submission - inside the loop.
     */
    public function test_the_printed_reports_do_not_query_per_row(): void
    {
        $admin = $this->makeUser('admin');
        $student = $this->makeStudent();

        foreach (['admin.reports.apel_a', 'admin.reports.apel_c'] as $report) {
            $this->seedApplications($student, 3);
            $small = $this->measure($admin, route($report));

            $this->seedApplications($student, 30);
            $large = $this->measure($admin, route($report));

            $this->assertLessThanOrEqual(
                $small['queries'] + 2,
                $large['queries'],
                "{$report} - small: {$small['queries']} queries, large: {$large['queries']}.",
            );
        }
    }

    /**
     * Both reports counted outcomes with status === 'Final Approved'.
     * StageMachine writes status as $stage->label($type), so an application
     * decided through the current code counted as neither approved nor
     * rejected and silently became "pending" on the official printed report.
     */
    public function test_the_report_counts_an_approval_made_through_the_current_workflow(): void
    {
        $admin = $this->makeUser('admin');
        $student = $this->makeStudent();

        $this->makeApplication($student, [
            'application_type' => 'APEL A',
            'stage' => ApelStage::APPROVED->value,
            // What StageMachine actually writes - not the legacy string.
            'status' => ApelStage::APPROVED->label('APEL A'),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.apel_a'));

        $response->assertOk();
        $response->assertViewHas('approved', 1);
        $response->assertViewHas('pending', 0);
    }

    /**
     * The per-row outcome column, and on APEL C the credit-hours total, both
     * keyed off status === 'Final Approved'. An approved application decided
     * through the current workflow fell to the else branch and printed in the
     * amber "pending" colour on the official report, and its credit hours were
     * never added to the total.
     */
    public function test_a_decided_application_reads_as_decided_on_the_printed_report(): void
    {
        $admin = $this->makeUser('admin');
        $student = $this->makeStudent();

        foreach ([['APEL A', 'admin.reports.apel_a'], ['APEL C', 'admin.reports.apel_c']] as [$type, $report]) {
            $this->makeApplication($student, [
                'application_type' => $type,
                'stage' => ApelStage::APPROVED->value,
                'status' => ApelStage::APPROVED->label($type),
            ]);

            $response = $this->actingAs($admin)->get(route($report));

            $response->assertOk();
            $response->assertViewHas('approved', 1);

            // The row must render as a pass, not fall through to pending.
            $response->assertSee('(Pass)', false);
        }
    }
}
