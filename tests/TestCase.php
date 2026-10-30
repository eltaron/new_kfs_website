<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Guard: tests must never touch the development database.
        if (config('database.connections.mysql.database') !== 'kfs_testing') {
            throw new \RuntimeException('Tests must run against kfs_testing only.');
        }
    }
}
