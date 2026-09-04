<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the questions an audit trail actually gets asked.
 *
 * activity_logs carried only created_at, which serves the dashboard's "latest
 * eight" and nothing else. Now that authentication events are recorded, the
 * queries that matter after an incident are "everything for this account" and
 * "every failed sign-in recently" - both of which had to scan the collection,
 * and the audit trail is the one collection that only ever grows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mongodb')->table('activity_logs', function ($collection) {
            // "What has happened to this account?" - newest first.
            $collection->index(['user_id' => 1, 'created_at' => -1], 'activity_user_recent');

            // "Show me every failed sign-in this week." Action first, because
            // it is the selective half of the pair.
            $collection->index(['action' => 1, 'created_at' => -1], 'activity_action_recent');

            // "Which addresses is this IP trying?" - the shape of a spray.
            $collection->index(['ip_address' => 1, 'created_at' => -1], 'activity_ip_recent');
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->table('activity_logs', function ($collection) {
            foreach (['activity_user_recent', 'activity_action_recent', 'activity_ip_recent'] as $name) {
                $collection->dropIndex($name);
            }
        });
    }
};
