<?php

declare(strict_types=1);

namespace Libsql\Laravel\Database;

use Illuminate\Database\Query\Builder;

class LibsqlQueryBuilder extends Builder
{
    public function exists(): bool
    {
        $this->applyBeforeQueryCallbacks();

        $results = $this->connection->select(
            query: $this->grammar->compileExists($this),
            bindings: $this->getBindings(),
            useReadPdo: ! $this->useWritePdo
        );

        return (bool) data_get($results, '0.exists', false);
    }
}
