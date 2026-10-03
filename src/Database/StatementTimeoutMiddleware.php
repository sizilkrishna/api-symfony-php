<?php

declare(strict_types=1);

namespace App\Database;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;

/**
 * Sets a PostgreSQL statement_timeout whenever the real connection is opened,
 * so the connection itself stays lazy (health checks never touch the database).
 */
final class StatementTimeoutMiddleware implements Middleware
{
    public function __construct(private readonly int $milliseconds)
    {
    }

    public function wrap(Driver $driver): Driver
    {
        return new class ($driver, $this->milliseconds) extends AbstractDriverMiddleware {
            public function __construct(Driver $driver, private readonly int $milliseconds)
            {
                parent::__construct($driver);
            }

            public function connect(array $params): Connection
            {
                $connection = parent::connect($params);
                $connection->exec(sprintf('SET statement_timeout = %d', $this->milliseconds));

                return $connection;
            }
        };
    }
}
