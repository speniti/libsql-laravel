<?php

declare(strict_types=1);

namespace Libsql\Laravel\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Libsql\Laravel\LibsqlServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => sprintf(
                'Libsql\\Laravel\\Tests\\Fixtures\\Factories\\%sFactory',
                class_basename($modelName)
            )
        );
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.connections', [
            'memory' => [
                'driver' => 'libsql',
                'database' => ':memory:',
            ],

            'remote' => [
                'driver' => 'libsql',
                'url' => 'http://127.0.0.1:8081',
            ],

            'embedded' => [
                'driver' => 'libsql',
                'database' => test_database_path('embedded.db'),
                'url' => 'http://127.0.0.1:8081',
            ],
        ]);

        config()->set('database.default', 'memory');
        config()->set('queue.default', 'sync');
    }

    protected function getPackageProviders($app): array
    {
        return [
            LibsqlServiceProvider::class,
        ];
    }
}
