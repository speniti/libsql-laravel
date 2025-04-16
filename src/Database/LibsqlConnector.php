<?php

declare(strict_types=1);

namespace Libsql\Laravel\Database;

use Illuminate\Database\Connectors\SQLiteConnector;

class LibsqlConnector extends SQLiteConnector
{
    /**
     * @param  array<mixed>  $config
     *
     * @phpstan-ignore method.childReturnType
     */
    public function connect(array $config): LibsqlDatabase /** @phpstan-ignore method.childReturnType */
    {
        return new LibsqlDatabase($config);
    }
}
