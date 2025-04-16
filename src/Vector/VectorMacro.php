<?php

declare(strict_types=1);

namespace Libsql\Laravel\Vector;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Libsql\Laravel\Database\LibsqlQueryGrammar;

class VectorMacro
{
    public static function create(): void
    {
        Blueprint::macro('vectorIndex', function (string $column, string $index) {
            $table = $this->getTable();

            /** @var Blueprint $this */
            return DB::statement("CREATE INDEX $index ON $table(libsql_vector_idx($column))");
        });

        /** @param array<mixed> $vector */
        Builder::macro('nearest', function (string $index, array $vector, int $limit = 10) {
            /** @var Builder $this */
            $statement = sprintf("vector_top_k('$index', '[%s]', $limit)", implode(',', $vector));
            $from = $this->from instanceof Expression
                ? $this->from->getValue(app(LibsqlQueryGrammar::class))
                : $this->from;

            return $this->joinSub(DB::table(DB::raw($statement)), 'v', "$from.rowid", '=', 'v.id');
        });
    }
}
