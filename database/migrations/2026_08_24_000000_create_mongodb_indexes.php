<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

/**
 * The application had no index on any collection. Two of the gaps were not
 * merely a performance concern - the code relies on uniqueness that nothing
 * enforced:
 *
 *  - users.email: registration checks uniqueness in PHP, which cannot prevent
 *    a concurrent duplicate insert.
 *  - assessment_submissions.application_id: five code paths create submission
 *    documents via read-then-write helpers. Without a unique key those are not
 *    atomic, and a duplicate lets finalizeApelC() read an ungraded document
 *    whose result is 'pending', bypassing the "cannot approve a failed
 *    assessment" guard.
 *
 * Every index below is derived from a query that actually exists in the code.
 */
return new class extends Migration
{
    private const CONNECTION = 'mongodb';

    public function up(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        $schema->table('users', function (Blueprint $collection) {
            $collection->unique('email');   // registration + both reset lookups
            $collection->index('role');     // rankEvaluators, evaluator lists
        });

        $schema->table('applications', function (Blueprint $collection) {
            $collection->index('user_id');                          // every student list
            $collection->index('evaluator_id');
            $collection->index('evaluator_2_id');
            $collection->index(['application_type', 'status']);     // admin + report filters
            $collection->index('submission_date');                  // every orderBy in admin views
        });

        $schema->table('assessment_submissions', function (Blueprint $collection) {
            $collection->unique('application_id');
            $collection->index('graded_by');                        // evaluator dashboard counts
        });

        $schema->table('assessment_papers', function (Blueprint $collection) {
            $collection->index('application_id');
            $collection->index('evaluator_id');
        });

        // Unbounded collection sorted on every admin dashboard load.
        $schema->table('activity_logs', function (Blueprint $collection) {
            $collection->index('created_at');
        });

        $schema->table('password_reset_tokens', function (Blueprint $collection) {
            $collection->unique('email');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        $schema->table('users', function (Blueprint $collection) {
            $collection->dropIndex('email_1');
            $collection->dropIndex('role_1');
        });

        $schema->table('applications', function (Blueprint $collection) {
            $collection->dropIndex('user_id_1');
            $collection->dropIndex('evaluator_id_1');
            $collection->dropIndex('evaluator_2_id_1');
            $collection->dropIndex('application_type_1_status_1');
            $collection->dropIndex('submission_date_1');
        });

        $schema->table('assessment_submissions', function (Blueprint $collection) {
            $collection->dropIndex('application_id_1');
            $collection->dropIndex('graded_by_1');
        });

        $schema->table('assessment_papers', function (Blueprint $collection) {
            $collection->dropIndex('application_id_1');
            $collection->dropIndex('evaluator_id_1');
        });

        $schema->table('activity_logs', function (Blueprint $collection) {
            $collection->dropIndex('created_at_1');
        });

        $schema->table('password_reset_tokens', function (Blueprint $collection) {
            $collection->dropIndex('email_1');
        });
    }
};
