<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();

        // This runs before RefreshDatabase or any other test lifecycle trait.
        $connection = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$connection}.database");
        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new \RuntimeException('Tests require SQLite :memory:. Refusing to touch a persistent database. Clear the cached configuration before testing.');
        }

        return $app;
    }
}
