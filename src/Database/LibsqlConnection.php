<?php

declare(strict_types=1);

namespace Libsql\Laravel\Database;

use Closure;
use Exception;
use Illuminate\Database\Connection;
use Illuminate\Filesystem\Filesystem;
use JsonException;
use Libsql\Transaction;
use PDO;
use PDOException;
use ReturnTypeWillChange;
use SQLite3;

class LibsqlConnection extends Connection
{
    /** @var array<mixed> */
    protected array $bindings = [];

    protected LibsqlDatabase $db;

    protected int $mode = PDO::FETCH_OBJ;

    /**
     * @var LibsqlDatabase|Closure
     *
     * @phpstan-ignore property.phpDocType
     */
    protected $readPdo;

    protected Transaction $tx;

    /** @param array<mixed> $config */
    public function __construct(LibsqlDatabase $db, string $database = ':memory:', string $tablePrefix = '', array $config = [])
    {
        parent::__construct(static fn () => $db, $database, $tablePrefix, $config);

        $this->db = $db;
        $this->schemaGrammar = $this->getDefaultSchemaGrammar();
    }

    /**
     * @param  string  $query
     * @param  array<mixed>  $bindings
     */
    public function affectingStatement($query, $bindings = []): int
    {
        $bindings = array_map(static fn ($binding) => is_bool($binding) ? (int) $binding : $binding, $bindings);

        /** @var int $affected */
        $affected = $this->run($query, $bindings, function (string $query, array $bindings) {
            if ($this->pretending()) {
                return 0;
            }

            $statement = $this->getPdo()->prepare($query);

            foreach ($bindings as $key => $value) {
                $statement->bindValue($key, $value);
            }

            $statement->execute();

            $this->recordsHaveBeenModified(($count = $statement->rowCount()) > 0);

            return $count;
        });

        return $affected;
    }

    /** @param array<mixed> $config */
    public function createReadPdo(array $config): ?LibsqlDatabase
    {
        $db = static fn () => new LibsqlDatabase($config);
        $this->setReadPdo($db);

        return $db();
    }

    /** @param ?string $value */
    public function escapeString($value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        return SQLite3::escapeString($value);
    }

    public function getConnectionMode(): string
    {
        return $this->db->getConnectionMode();
    }

    public function getDefaultPostProcessor(): LibsqlQueryProcessor
    {
        return new LibsqlQueryProcessor;
    }

    public function getPdo(): LibsqlDatabase /** @phpstan-ignore method.childReturnType */
    {
        return $this->db;
    }

    public function getSchemaBuilder(): LibsqlSchemaBuilder
    {
        if ($this->schemaGrammar === null) {
            $this->useDefaultSchemaGrammar();
        }

        return new LibsqlSchemaBuilder($this->db, $this);
    }

    public function getSchemaState(?Filesystem $files = null, ?callable $processFactory = null): LibsqlSchemaState
    {
        return new LibsqlSchemaState($this, $files, $processFactory);
    }

    public function getServerVersion(): string
    {
        return $this->db->version();
    }

    /** @param array<mixed> $bindings */
    public function insert($query, $bindings = []): bool
    {
        return $this->affectingStatement($query, $bindings) > 0;
    }

    public function inTransaction(): bool
    {
        return $this->db->inTransaction();
    }

    public function isUniqueConstraintError(Exception $exception): bool
    {
        return (bool) preg_match(
            '#(column(s)? .* (is|are) not unique|UNIQUE constraint failed: .*)#i',
            $exception->getMessage()
        );
    }

    public function query(): LibsqlQueryBuilder
    {
        return new LibsqlQueryBuilder($this, $this->getQueryGrammar(), $this->getPostProcessor());
    }

    public function quote(mixed $input): string
    {
        if ($input === null) {
            return 'null';
        }

        if (is_string($input)) {
            return "'".$this->escapeString($input)."'";
        }

        if (is_resource($input)) {
            /** @var string $contents */
            $contents = stream_get_contents($input);

            return $this->escapeBinary($contents);
        }

        /** @var string $input */
        return $this->escapeBinary($input);
    }

    /**
     * @param  array<mixed>  $bindings
     * @return array<mixed>
     *
     * @throws JsonException
     */
    public function select($query, $bindings = [], $useReadPdo = true): array|object
    {
        $bindings = array_map(static fn ($binding) => is_bool($binding) ? (int) $binding : $binding, $bindings);

        /** @var array<mixed> $data */
        $data = $this->run($query, $bindings, function (string $query, array $bindings) {
            if ($this->pretending()) {
                return [];
            }

            $results = (array) $this->getPdo()->prepare($query)->query($bindings);

            return array_map(
                static function ($row) {
                    assert(is_array($row));

                    return array_map(
                        static fn ($value) => is_resource($value)
                            ? stream_get_contents($value)
                            : $value,
                        $row
                    );
                },
                $results
            );
        });

        $values = array_values($data);

        return match ($this->mode) {
            PDO::FETCH_BOTH => array_merge($data, $values),
            PDO::FETCH_ASSOC, PDO::FETCH_NAMED => $data,
            PDO::FETCH_NUM => $values,
            PDO::FETCH_OBJ => $this->toObject($data),
            default => throw new PDOException('Unsupported fetch mode.'),
        };
    }

    /**
     * @param  array<mixed>  $bindings
     *
     * @throws JsonException
     */
    public function selectOne($query, $bindings = [], $useReadPdo = true)
    {
        $this->setFetchMode(PDO::FETCH_ASSOC);

        /** @var array<mixed> $records */
        $records = $this->select($query, $bindings, $useReadPdo);

        return array_shift($records);
    }

    public function setFetchMode(int $mode): bool
    {
        $this->mode = $mode;

        return true;
    }

    /** @throws Exception */
    public function sync(): void
    {
        $this->db->sync();
    }

    /**
     * @param  array<mixed>  $data
     * @return array<mixed>
     *
     * @throws JsonException
     */
    public function toObject(array $data): array
    {
        return array_map(
            static function ($item) {
                assert(is_array($item));

                return (object) array_map(
                    static fn ($value) => is_array($value) && ! is_vector($value)
                        ? json_encode($value, JSON_THROW_ON_ERROR)
                        : $value,
                    $item
                );
            },
            $data
        );
    }

    protected function getDefaultQueryGrammar(): LibsqlQueryGrammar
    {
        return new LibsqlQueryGrammar($this);
    }

    #[ReturnTypeWillChange]
    protected function getDefaultSchemaGrammar(): LibsqlSchemaGrammar
    {
        return new LibsqlSchemaGrammar($this);
    }
}
