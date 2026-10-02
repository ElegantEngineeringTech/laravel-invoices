<?php

declare(strict_types=1);

namespace Elegantly\Invoices\Tests;

use Elegantly\Invoices\InvoiceServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Elegantly\\Invoices\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            InvoiceServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('money.default_currency', 'USD');
    }

    protected function defineDatabaseMigrations(): void
    {
        foreach (InvoiceServiceProvider::MIGRATIONS as $migration) {
            (require __DIR__."/../database/migrations/{$migration}.php.stub")->up();
        }
    }
}
