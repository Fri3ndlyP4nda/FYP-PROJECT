<?php

namespace Tests;

use Illuminate\Support\Facades\DB;

/**
 * Truncate the MongoDB test database between tests.
 *
 * Laravel's RefreshDatabase is not usable here. It wraps each test in a
 * transaction and rolls back, which requires migrations that describe the
 * schema — and this application has no migrations for its real collections,
 * because MongoDB creates them implicitly on first write. A standalone mongod
 * also has no replica set, so multi-document transactions are unavailable.
 *
 * Dropping the collections is the equivalent that actually works: it is fast
 * (these are small test datasets), it leaves no state between tests, and it
 * needs no schema definition.
 *
 * Guarded against pointing at anything but a test database — a mistaken
 * DB_DATABASE would otherwise wipe real applicant records.
 */
trait RefreshesMongoDatabase
{
    protected function refreshMongoDatabase(): void
    {
        $name = config('database.connections.mongodb.database');

        if (! is_string($name) || ! str_contains($name, 'testing')) {
            $this->fail(
                "Refusing to truncate '{$name}': the test database name must contain "
                . "'testing'. Check DB_DATABASE in phpunit.xml — this guard exists so a "
                . "misconfigured suite cannot delete real applicant data."
            );
        }

        $database = DB::connection('mongodb')->getMongoDB();

        foreach ($database->listCollections() as $collection) {
            $database->selectCollection($collection->getName())->deleteMany([]);
        }
    }
}
