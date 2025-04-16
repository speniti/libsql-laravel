<?php

declare(strict_types=1);

namespace Libsql\Laravel\Database;

use Illuminate\Database\Query\Processors\SQLiteProcessor;

class LibsqlQueryProcessor extends SQLiteProcessor
{
    /**
     * @param  array<mixed>  $results
     * @param  string  $sql
     * @return array<mixed>
     */
    public function processColumns($results, $sql = ''): array
    {
        $hasPrimaryKey = array_sum(array_column($results, 'primary')) === 1;

        return array_map(static function ($result, $index) use ($hasPrimaryKey, $sql) {
            $result = (object) $result;

            assert(is_string($result->type));
            $type = mb_strtolower($result->type);

            assert(is_string($result->name));
            $safeName = preg_quote($result->name, '/');

            if (empty($safeName)) {
                preg_match_all('/[(,]\s*[\'"`]?([a-zA-Z_]\w*)[\'"`]?\s+[a-zA-Z]+/i', $sql, $matches);

                $safeName = $matches[1][$index];
            }

            $collation = preg_match(
                '/\b'.$safeName.'\b[^,(]+(?:\([^()]+\)[^,]*)?(?:(?:default|check|as)\s*(?:\(.*?\))?[^,]*)*collate\s+["\'`]?(\w+)/i',
                $sql,
                $matches
            ) === 1 ? mb_strtolower($matches[1]) : null;

            $isGenerated = in_array($result->extra, [2, 3], true);

            $expression = $isGenerated && preg_match(
                '/\b'.$safeName.'\b[^,]+\s+as\s+\(((?:[^()]+|\((?:[^()]+|\([^()]*\))*\))*)\)/i',
                $sql,
                $matches
            ) === 1 ? $matches[1] : null;

            assert(is_numeric($result->extra));

            return [
                'name' => $safeName,
                'type_name' => strtok($type, '(') ?: '',
                'type' => $type,
                'collation' => $collation,
                'nullable' => (bool) $result->nullable,
                'default' => $result->default,
                'auto_increment' => $hasPrimaryKey && $result->primary && $type === 'integer',
                'comment' => null,
                'generation' => $isGenerated ? [
                    'type' => match ((int) $result->extra) {
                        3 => 'stored',
                        2 => 'virtual',
                        default => null,
                    },
                    'expression' => $expression,
                ] : null,
            ];
        }, $results, array_keys($results));
    }

    /**
     * @param  array<mixed>  $results
     * @return array<mixed>
     */
    public function processTables($results): array
    {
        return array_map(static fn ($result) => [
            'name' => data_get($result, 'name'),
            'schema' => data_get($result, 'schema'),
            'size' => data_get($result, 'size'),
            'comment' => data_get($result, 'comment'),
            'collation' => data_get($result, 'collation'),
            'engine' => data_get($result, 'engine'),
        ], $results);
    }

    /**
     * @param  array<mixed>  $results
     * @return array<mixed>
     */
    public function processViews($results): array
    {
        return array_map(static fn ($result) => [
            'name' => data_get($result, 'name'),
            'schema' => data_get($result, 'schema'),
            'definition' => data_get($result, 'definition'),
        ], $results);
    }
}
