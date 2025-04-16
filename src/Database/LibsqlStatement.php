<?php

declare(strict_types=1);

namespace Libsql\Laravel\Database;

use function array_map;

use DateTimeInterface;
use Exception;
use JsonException;
use Libsql\Statement;
use PDO;
use PDOException;
use ReturnTypeWillChange;

class LibsqlStatement
{
    protected int $affectedRows = 0;

    /** @var array<mixed> */
    protected array $bindings = [];

    protected int $mode = PDO::FETCH_OBJ;

    /** @var array<mixed>|object */
    protected array|object $response = [];

    public function __construct(
        private readonly Statement $statement,
        protected string $query
    ) {}

    public function bindValue(string|int $parameter, mixed $value = null): self
    {
        $this->bindings[$parameter] = $value;
        $this->bindings = $this->parameterCasting($this->bindings);

        return $this;
    }

    public function closeCursor(): void
    {
        $this->statement->reset();
    }

    /** @param array<mixed> $parameters */
    public function execute(array $parameters = []): bool
    {
        try {
            if (empty($parameters)) {
                $parameters = $this->bindings;
            }

            foreach ($parameters as $key => $value) {
                $this->statement->bind([$key => $value]);
            }

            if (str_starts_with(mb_strtolower($this->query), 'select')) {
                $queryRows = $this->statement->query()->fetchArray();
                $this->affectedRows = count($queryRows);
            } else {
                $this->affectedRows = $this->statement->execute();
            }

            return true;
        } catch (Exception) {
            return false;
        }
    }

    /** @return array<mixed>|object|false */
    #[ReturnTypeWillChange]
    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOffset = 0): array|object|false
    {
        if ($mode === PDO::FETCH_DEFAULT) {
            $mode = $this->mode;
        }

        foreach ($this->parameterCasting($this->bindings) as $key => $value) {
            $this->statement->bind([$key => $value]);
        }

        $row = $this->statement->query()
            ->fetchArray()[$cursorOffset];

        if ($this->response === $row) {
            return false;
        }

        $this->response = $row;
        $values = array_values($row);

        return match ($mode) {
            PDO::FETCH_BOTH => array_merge($row, $values),
            PDO::FETCH_ASSOC, PDO::FETCH_NAMED => $row,
            PDO::FETCH_NUM => $values,
            PDO::FETCH_OBJ => (object) $row,
            default => throw new PDOException('Unsupported fetch mode.'),
        };
    }

    /** @return array<mixed>|object */
    #[ReturnTypeWillChange]
    public function fetchAll(int $mode = PDO::FETCH_DEFAULT): array|object
    {
        if ($mode === PDO::FETCH_DEFAULT) {
            $mode = $this->mode;
        }

        foreach ($this->parameterCasting($this->bindings) as $key => $value) {
            $this->statement->bind([$key => $value]);
        }

        $rows = $this->statement->query()->fetchArray();
        $values = array_values($this->parameterCasting($rows));

        return match ($mode) {
            PDO::FETCH_BOTH => array_merge($rows, $values),
            PDO::FETCH_ASSOC, PDO::FETCH_NAMED => $rows,
            PDO::FETCH_NUM => $values,
            PDO::FETCH_OBJ => (object) $rows,
            default => throw new PDOException('Unsupported fetch mode.'),
        };
    }

    public function getAffectedRows(): int
    {
        return $this->affectedRows;
    }

    public function nextRowset(): bool
    {
        return false;
    }

    public function prepare(string $query): self
    {
        return new self($this->statement, $query);
    }

    /**
     * @param  array<mixed>  $parameters
     * @return object|array<mixed>
     */
    public function query(array $parameters = []): object|array
    {
        foreach ($parameters as $key => $value) {
            $this->bindValue($key, $value);
        }

        foreach ($this->bindings as $key => $value) {
            $this->statement->bind([$key => $value]);
        }

        $rows = $this->decode($this->statement->query()->fetchArray());

        if (empty($parameters)) {
            $values = array_values($rows);

            return match ($this->mode) {
                PDO::FETCH_BOTH => array_merge($rows, $values),
                PDO::FETCH_ASSOC, PDO::FETCH_NAMED => $rows,
                PDO::FETCH_NUM => $values,
                PDO::FETCH_OBJ => (object) $rows,
                default => throw new PDOException('Unsupported fetch mode.'),
            };
        }

        return match ($this->mode) {
            PDO::FETCH_OBJ => (object) $rows,
            PDO::FETCH_NUM => array_values($rows),
            default => collect($rows)
        };
    }

    public function rowCount(): int
    {
        return $this->affectedRows;
    }

    public function setFetchMode(int $mode): bool
    {
        $this->mode = $mode;

        return true;
    }

    /**
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    private function decode(array $data): array
    {
        return array_map(
            static function ($row) {
                assert(is_array($row));

                return array_map(static function ($value) {
                    if (! is_string($value) || is_numeric($value) || is_datetime($value)) {
                        return $value;
                    }

                    try {
                        return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                    } catch (JsonException) {
                        if (preg_match(
                            '/^(?:[A-Za-z0-9+\/]{4})*(?:[A-Za-z0-9+\/]{2}==|[A-Za-z0-9+\/]{3}=)?$/',
                            $value
                        )) {
                            return base64_decode(base64_decode($value));
                        }

                        return $value;
                    }
                }, $row);
            },
            $data
        );
    }

    /**
     * @param  array<mixed>  $parameters
     * @return array<mixed>
     */
    private function parameterCasting(array $parameters): array
    {
        return collect(array_values($parameters))
            ->map(static function (mixed $value) {
                if (is_blob($value)) {
                    assert(is_string($value));

                    return base64_encode(base64_encode($value));
                }

                if (is_bool($value)) {
                    return (int) $value;
                }

                if (is_vector($value)) {
                    assert(is_array($value));

                    return json_encode($value, JSON_THROW_ON_ERROR);
                }

                if ($value instanceof DateTimeInterface) {
                    return $value->format('Y-m-d H:i:s');
                }

                return $value;
            })->toArray();
    }
}
