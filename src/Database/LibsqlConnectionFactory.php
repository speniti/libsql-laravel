<?php

declare(strict_types=1);

namespace Libsql\Laravel\Database;

use Illuminate\Database\Connectors\ConnectionFactory;

class LibsqlConnectionFactory extends ConnectionFactory
{
    /** @param  array<mixed>  $config */
    public function createConnector(array $config): LibsqlConnector
    {
        return new LibsqlConnector();
    }

    /** @param  array<mixed>  $config */
    protected function createConnection($driver, $connection, $database, $prefix = '', array $config = []): LibsqlConnection
    {
        /** @var string $host */
        $host = data_get($config, 'host', '');

        /** @var string $port */
        $port = data_get($config, 'port', '');

        $config['url'] = empty($port) ? "$driver://$host" : "$driver://$host:$port";

        $config['driver'] = 'libsql';

        return new LibsqlConnection(new LibsqlDatabase($config), $database, $prefix, $config);
    }

    /** @param  array<mixed>  $config */
    protected function createSingleConnection(array $config): LibsqlConnection
    {
        $pdo = $this->createPdoResolver($config);

        /** @var string $driver */
        $driver = data_get($config, 'driver', 'libsql');

        /** @var string $database */
        $database = data_get($config, 'database', '');

        /** @var string $prefix */
        $prefix = data_get($config, 'prefix', '');

        return $this->createConnection($driver, $pdo, $database, $prefix, $config);
    }
}
