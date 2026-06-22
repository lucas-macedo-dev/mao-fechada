<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Force every test onto an isolated in-memory SQLite database.
     *
     * Runs inside refreshApplication(), BEFORE RefreshDatabase migrates,
     * so tests can never touch the real MySQL database — even if the config
     * is cached with a mysql connection (bootstrap/cache/config.php).
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.driver', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('database.connections.sqlite.url', null);

        return $app;
    }
}
