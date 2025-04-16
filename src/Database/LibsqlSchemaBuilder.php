<?php

declare(strict_types=1);

namespace Libsql\Laravel\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\SQLiteBuilder;
use Libsql\Laravel\Exceptions\FeatureNotSupportedException;
use PDO;

class LibsqlSchemaBuilder extends SQLiteBuilder
{
    public function __construct(protected LibsqlDatabase $db, Connection $connection)
    {
        parent::__construct($connection);
    }

    /** @return never-returns */
    public function createDatabase($name): void
    {
        throw new FeatureNotSupportedException('Creating database is not supported in LibSQL database.');
    }

    public function dropAllTables(): void
    {
        $this->dropAllTriggers();
        $this->dropAllIndexes();

        $this->db->exec($this->grammar()->compileDisableForeignKeyConstraints());

        $statement = $this->db->prepare($this->grammar()->compileDropAllTables());
        $this->executeDropStatement($statement);

        $this->db->exec($this->grammar()->compileEnableForeignKeyConstraints());
    }

    public function dropAllViews(): void
    {
        $statement = $this->db->prepare($this->grammar()->compileDropAllViews());
        $this->executeDropStatement($statement);
    }

    /** @return never-returns */
    public function dropDatabaseIfExists($name): void
    {
        throw new FeatureNotSupportedException('Dropping database is not supported in LibSQL database.');
    }

    /** @return array<mixed> */
    public function getTables($schema = null): array
    {
        assert($this->grammar instanceof LibsqlSchemaGrammar);

        try {
            /** @var bool $withSize */
            $withSize = $this->connection->scalar($this->grammar->compileDbstatExists());
        } catch (QueryException) {
            $withSize = false;
        }

        return $this->connection
            ->getPostProcessor()
            ->processTables(
                $this->connection->selectFromWriteConnection(
                    $this->grammar->compileTables($schema, $withSize)
                )
            );
    }

    protected function dropAllIndexes(): void
    {
        $statement = $this->db->prepare($this->grammar()->compileDropAllIndexes());

        $this->executeDropStatement($statement);
    }

    protected function dropAllTriggers(): void
    {
        $statement = $this->db->prepare($this->grammar()->compileDropAllTriggers());
        $this->executeDropStatement($statement);
    }

    protected function grammar(): LibsqlSchemaGrammar
    {
        return new LibsqlSchemaGrammar($this->connection);
    }

    private function executeDropStatement(LibsqlStatement $statement): void
    {
        $statement->setFetchMode(PDO::FETCH_ASSOC);

        /** @var array<mixed> $result */
        $result = $statement->query();

        collect($result)->each(function ($query) {
            assert(is_array($query));
            /** @var string $sql */
            $sql = data_get(array_values($query), '0', '');

            $this->db->query($sql);
        });
    }
}
