<?php

declare(strict_types=1);

namespace Libsql\Laravel\Database;

use Exception;
use Libsql\Connection;
use Libsql\Database;
use Libsql\Transaction;
use PDO;
use PDOException;
use RuntimeException;
use SQLite3;

class LibsqlDatabase
{
    private string $connection_mode;

    private Connection $db;

    private bool $in_transaction = false;

    /** @var array<string, int|null> */
    private array $lastInsertIds = [];

    private Database $libsql;

    private int $mode = PDO::FETCH_ASSOC;

    private Transaction $tx;

    /** @param array<mixed> $config */
    public function __construct(array $config)
    {
        $config = $this->parseConfig($config);
        $this->detectConnectionMode($config);

        $this->libsql = new Database(...$config);
        $this->db = $this->libsql->connect();
    }

    public function beginTransaction(): bool
    {
        if ($this->inTransaction()) {
            throw new PDOException('Already in a transaction');
        }

        $this->in_transaction = true;
        $this->tx = $this->db->transaction();

        return true;
    }

    public function commit(): bool
    {
        if (! $this->inTransaction()) {
            throw new PDOException('No active transaction');
        }

        $this->tx->commit();
        $this->in_transaction = false;

        return true;
    }

    public function escapeString(?string $input): string
    {
        if ($input === null) {
            return 'null';
        }

        return SQLite3::escapeString($input);
    }

    public function exec(string $queryStatement): int
    {
        $statement = $this->prepare($queryStatement);
        $statement->execute();

        return $statement->rowCount();
    }

    public function getConnectionMode(): string
    {
        return $this->connection_mode;
    }

    public function inTransaction(): bool
    {
        return $this->in_transaction;
    }

    public function lastInsertId(?string $name = null): int|string
    {
        /** @var int $last */
        $last = data_get($this->lastInsertIds, $name ?? 'id', $this->db->lastInsertId());

        return (string) $last;
    }

    public function prepare(string $sql): LibsqlStatement
    {
        return new LibsqlStatement(($this->inTransaction() ? $this->tx : $this->db)->prepare($sql), $sql);
    }

    /**
     * @param  array<mixed>  $params
     * @return array<mixed>
     */
    public function query(string $sql, array $params = []): array
    {
        $results = $this->db->query($sql, $params)->fetchArray();
        $values = array_values($results);

        return match ($this->mode) {
            PDO::FETCH_BOTH => array_merge($results, $values),
            PDO::FETCH_ASSOC, PDO::FETCH_NAMED, PDO::FETCH_OBJ => $results,
            PDO::FETCH_NUM => $values,
            default => throw new PDOException('Unsupported fetch mode.'),
        };
    }

    public function quote(?string $input): string
    {
        if ($input === null) {
            return 'null';
        }

        return sprintf("'%s'", $this->escapeString($input));
    }

    public function rollBack(): bool
    {
        if (! $this->inTransaction()) {
            throw new PDOException('No active transaction');
        }

        $this->tx->rollback();
        $this->in_transaction = false;

        return true;
    }

    public function setFetchMode(int $mode): bool
    {
        $this->mode = $mode;

        return true;
    }

    public function setLastInsertId(?string $name = null, ?int $value = null): void
    {
        $this->lastInsertIds[$name ?? 'id'] = $value;
    }

    /** @throws Exception */
    public function sync(): void
    {
        if ($this->connection_mode !== 'remote_replica') {
            throw new RuntimeException("[Libsql:$this->connection_mode] Sync is only available for Remote Replica Connection.", 1);
        }

        $this->libsql->sync();
    }

    public function version(): string
    {
        // TODO: return an actual version from libSQL binary.
        return '0.0.1';
    }

    /** @param array<mixed> $config */
    private function detectConnectionMode(array $config): void
    {
        $database = data_get($config, 'path', '');
        $url = data_get($config, 'url', '');

        // TODO: add a ConnectionMode enum.
        $mode = match (true) {
            $database === ':memory:' => 'memory',
            ! empty($database) && empty($url) => 'local',
            empty($database) && ! empty($url) => 'remote',
            ! empty($database) && ! empty($url) => 'remote_replica',
            default => 'unknown',
        };

        $this->connection_mode = $mode;
    }

    /**
     * @param  array<mixed>  $config
     * @return array{
     *     path: string, url: string|null, authToken: string|null,
     *     encryptionKey: string|null, syncInterval: int,
     *     readYourWrites: bool, webpki: bool
     * }
     */
    private function parseConfig(array $config): array
    {
        /** @var string $path */
        $path = data_get($config, 'database', '');

        /** @var ?string $url */
        $url = data_get($config, 'url');

        /** @var ?string $authToken */
        $authToken = data_get($config, 'password');

        /** @var ?string $encryptionKey */
        $encryptionKey = data_get($config, 'encryptionKey');

        /** @var int $syncInterval */
        $syncInterval = data_get($config, 'syncInterval', 0);

        /** @var bool $readYourWrites */
        $readYourWrites = data_get($config, 'read_your_writes', true);

        /** @var bool $webpki */
        $webpki = data_get($config, 'webpki', false);

        return compact('path', 'url', 'authToken', 'encryptionKey', 'syncInterval', 'readYourWrites', 'webpki');
    }
}
