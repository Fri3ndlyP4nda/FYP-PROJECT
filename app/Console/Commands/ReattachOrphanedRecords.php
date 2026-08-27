<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\AssessmentPaper;
use App\Models\AssessmentSubmission;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Re-point records whose owning user no longer exists.
 *
 * Every user was deleted on 2026-08-24, which left all 15 applications, their
 * submissions, their papers and the activity log pointing at user ids that are
 * gone. The views resolve names with User::where('_id', ...)->value('name'),
 * so every one of those records rendered "N/A" for its student and evaluator —
 * the demo data spans the full workflow, Draft through Final Approved, and was
 * unusable for a walkthrough.
 *
 * This attaches orphans to the seeded account of the matching role rather than
 * deleting them, so the workflow states are preserved. It is idempotent: a
 * record already owned by a live user is left alone.
 *
 * Run with --dry to see what would change without writing.
 */
class ReattachOrphanedRecords extends Command
{
    protected $signature = 'apel:reattach-orphans {--dry : Report what would change without writing}';

    protected $description = 'Re-point applications, submissions, papers and logs whose owning user was deleted';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');

        $student = User::where('role', 'student')->orderBy('_id')->first();
        $evaluator = User::where('role', 'evaluator')->orderBy('_id')->first();
        $admin = User::where('role', 'admin')->orderBy('_id')->first();

        if (! $student || ! $evaluator || ! $admin) {
            $this->error('Need one live user per role. Run: php artisan db:seed --class=UserSeeder');

            return self::FAILURE;
        }

        // pluck('_id') returns nulls on this driver — the primary key is exposed
        // as 'id', and _id is only populated on a hydrated model. Getting this
        // wrong makes whereNotIn match every row, so the command reports every
        // record as an orphan and rewrites data that was already correct.
        $live = User::get()->map(fn (User $u) => (string) $u->_id)->all();

        $this->line($dry ? 'Dry run — nothing will be written.' : 'Re-pointing orphaned records.');
        $this->newLine();

        $plan = [
            ['applications.user_id',      Application::class,          'user_id',      (string) $student->_id],
            ['applications.student_id',   Application::class,          'student_id',   (string) $student->_id],
            ['applications.evaluator_id', Application::class,          'evaluator_id', (string) $evaluator->_id],
            ['applications.evaluator_2_id', Application::class,        'evaluator_2_id', (string) $evaluator->_id],
            ['submissions.student_id',    AssessmentSubmission::class, 'student_id',   (string) $student->_id],
            ['submissions.graded_by',     AssessmentSubmission::class, 'graded_by',    (string) $evaluator->_id],
            ['papers.evaluator_id',       AssessmentPaper::class,      'evaluator_id', (string) $evaluator->_id],
            ['activity_logs.user_id',     ActivityLog::class,          'user_id',      (string) $admin->_id],
        ];

        $total = 0;

        foreach ($plan as [$label, $model, $field, $newId]) {
            // Only rows that actually name a missing user. A null field means the
            // step never happened, which is real workflow state, not an orphan.
            $query = $model::whereNotNull($field)
                ->where($field, '!=', '')
                ->whereNotIn($field, $live);

            $count = (clone $query)->count();

            if ($count > 0 && ! $dry) {
                $query->update([$field => $newId]);
            }

            $total += $count;
            $this->line(sprintf('  %-30s %s%d', $label, $count > 0 ? '' : 'no orphans (', $count).($count > 0 ? '' : ')'));
        }

        $this->newLine();
        $this->info($dry
            ? "{$total} record(s) would be re-pointed."
            : "{$total} record(s) re-pointed.");

        return self::SUCCESS;
    }
}
