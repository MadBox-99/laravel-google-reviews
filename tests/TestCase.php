<?php

namespace Cegem360\GoogleReviews\Tests;

use Cegem360\GoogleReviews\GoogleReviewsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [GoogleReviewsServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        foreach (['create_google_review_places_table', 'create_google_reviews_table'] as $name) {
            $migration = require __DIR__.'/../database/migrations/'.$name.'.php.stub';
            $migration->up();
        }
    }
}
