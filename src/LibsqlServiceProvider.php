<?php

declare(strict_types=1);

namespace Libsql\Laravel;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Config;
use Libsql\Laravel\Database\LibsqlConnection;
use Libsql\Laravel\Database\LibsqlConnectionFactory;
use Libsql\Laravel\Database\LibsqlConnector;
use Libsql\Laravel\Vector\VectorMacro;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LibsqlServiceProvider extends PackageServiceProvider
{
    public function boot(): void
    {
        parent::boot();
        if (config('database.default') !== 'libsql') {
            return;
        }
    }

    public function configurePackage(Package $package): void
    {
        $package->name('libsql-laravel');
    }

    public function register(): void
    {
        parent::register();

        VectorMacro::create();

        $this->app->singleton('db.factory', function (Container $app) {
            return new LibsqlConnectionFactory($app);
        });

        $this->app->scoped(LibsqlManager::class, function () {
            return new LibsqlManager(Config::array('database.connections.libsql'));
        });

        $this->app->resolving('db', function (DatabaseManager $db) {
            $db->extend('libsql', function (array $config, string $name) {
                $driver = data_get($config, 'driver', 'libsql');
                $database = data_get($config, 'database', '');
                $prefix = data_get($config, 'prefix', '');

                assert(is_string($database) && is_string($prefix));

                $config = [...$config, ...compact('name', 'driver', 'database', 'prefix')];

                $connection = new LibsqlConnection(
                    db: (new LibsqlConnector())->connect($config),
                    database: $database,
                    tablePrefix: $prefix,
                    config: $config
                );

                app()->instance(LibsqlConnection::class, $connection);

                $connection->createReadPdo($config);

                return $connection;
            });
        });
    }
}
