<?php

namespace Tests\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;

trait UsesTempDatabase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app = parent::createApplication();

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('database.connections.sqlite.foreign_key_constraints', false);

        if ($app['config']->get('database.default') !== 'sqlite') {
            throw new \RuntimeException('Refusing to run: temp database is not active.');
        }

        return $app;
    }
}
