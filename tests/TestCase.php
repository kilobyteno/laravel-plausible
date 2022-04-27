<?php

namespace Kilobyteno\LaravelPlausible\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kilobyteno\LaravelPlausible\LaravelPlausibleServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        /*
        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Kilobyteno\\Plausible\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
        */
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelPlausibleServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_laravel-plausible_table.php.stub';
        $migration->up();
        */
    }
}
