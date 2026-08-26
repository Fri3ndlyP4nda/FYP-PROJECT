<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use Tests\FeatureTestCase;

/**
 * Proves the test harness itself works before anything relies on it.
 *
 * If these fail, every other feature test is meaningless — they would be
 * asserting against a database that is not there, not clean, or not the one
 * the application actually uses.
 */
class DatabaseHarnessTest extends FeatureTestCase
{
    public function test_it_talks_to_a_real_mongodb_and_not_sqlite(): void
    {
        $this->assertSame('mongodb', config('database.default'));
        $this->assertStringContainsString(
            'testing',
            config('database.connections.mongodb.database'),
            'The suite must never point at the runtime database.'
        );

        $user = $this->makeStudent();

        $this->assertNotNull($user->_id);
        $this->assertSame('student', $user->fresh()->role);
    }

    public function test_the_unique_email_rule_resolves_against_mongodb(): void
    {
        // This is the exact query that killed the previous suite: `unique` runs
        // against the DEFAULT connection, which used to be an empty sqlite
        // database with no users table.
        $this->makeStudent(['email' => 'taken@apel.test']);

        $validator = validator(
            ['email' => 'taken@apel.test'],
            ['email' => 'required|email|unique:users,email']
        );

        $this->assertTrue($validator->fails(), 'unique:users,email did not see the existing document.');
    }

    public function test_each_test_starts_from_a_clean_database(): void
    {
        $this->assertSame(0, User::count(), 'State leaked in from a previous test.');
        $this->assertSame(0, Application::count());

        $this->makeApplication($this->makeStudent());

        $this->assertSame(1, Application::count());
    }

    public function test_the_clean_database_assertion_above_is_not_vacuous(): void
    {
        // Runs after the previous test, which left one user and one application
        // behind. If truncation silently stopped working, that test would still
        // pass on a fresh run and only fail on the second - this one fails
        // immediately instead.
        $this->assertSame(0, User::count());
        $this->assertSame(0, Application::count());
    }
}
