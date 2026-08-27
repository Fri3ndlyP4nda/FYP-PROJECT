<?php

namespace Tests;

/**
 * Base for tests that touch the database.
 *
 * Pure-domain tests (see tests/Unit) should extend TestCase instead — they need
 * no database and run an order of magnitude faster without one.
 */
abstract class FeatureTestCase extends TestCase
{
    use MakesApelRecords;
    use RefreshesMongoDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->refreshMongoDatabase();
    }
}
