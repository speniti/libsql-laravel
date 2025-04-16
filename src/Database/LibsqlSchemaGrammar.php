<?php

declare(strict_types=1);

namespace Libsql\Laravel\Database;

use Illuminate\Database\Schema\Grammars\SQLiteGrammar;
use Illuminate\Support\Fluent;
use Override;
use RuntimeException;

class LibsqlSchemaGrammar extends SQLiteGrammar
{
    public function compileDropAllIndexes(): string
    {
        return "select 'drop index if exists \"' || name || '\";' from sqlite_schema where type = 'index' and name not like 'sqlite_%'";
    }

    public function compileDropAllTables($schema = null): string
    {
        return "select 'drop table if exists \"' || name || '\";' from sqlite_schema where type = 'table' and name not like 'sqlite_%'";
    }

    public function compileDropAllTriggers(): string
    {
        return "select 'drop trigger if exists \"' || name || '\";' from sqlite_schema where type = 'trigger' and name not like 'sqlite_%'";
    }

    public function compileDropAllViews($schema = null): string
    {
        return "select 'drop view if exists \"' || name || '\";' from sqlite_schema where type = 'view'";
    }

    public function compileViews($schema): string
    {
        return "select name, sql as definition from sqlite_master where type = 'view' order by name";
    }

    /** @param Fluent<string, string> $column */
    public function typeVector(Fluent $column): string
    {
        if (isset($column->dimensions) && $column->dimensions !== '') {
            assert(is_int($column->dimensions));

            return "F32_BLOB($column->dimensions)";
        }

        throw new RuntimeException('Dimension must be set for vector embedding');
    }

    /** @param Fluent<string, string> $value */
    #[Override]
    public function wrap($value, bool $prefixAlias = false): string
    {
        return str_replace('"', '\'', parent::wrap($value));
    }
}
