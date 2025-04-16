<?php

declare(strict_types=1);

namespace Libsql\Laravel;

use BadMethodCallException;
use Illuminate\Support\Collection;
use Libsql\Laravel\Database\LibsqlDatabase;

class LibsqlManager
{
    protected LibsqlDatabase $client;

    /** @var Collection<string, mixed> */
    protected Collection $config;

    /** @param array<mixed> $config */
    public function __construct(array $config = [])
    {
        $this->config = new Collection($config);
        $this->client = new LibsqlDatabase($config);
    }

    /** @param array<mixed> $arguments */
    public function __call(string $method, array $arguments = []): mixed
    {
        if (! method_exists($this->client, $method)) {
            throw new BadMethodCallException(
                sprintf('Call to undefined method %s::%s()', static::class, $method)
            );
        }

        return $this->client->$method(...$arguments);
    }
}
