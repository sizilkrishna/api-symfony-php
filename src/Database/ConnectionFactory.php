<?php

declare(strict_types=1);

namespace App\Database;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;

final class ConnectionFactory
{
    /**
     * The returned connection is lazy: nothing connects until the first query.
     * $statementTimeoutMs = 0 disables the timeout.
     */
    public static function create(string $url, int $statementTimeoutMs = 0): Connection
    {
        $params = (new DsnParser([
            'postgresql' => 'pdo_pgsql',
            'postgres' => 'pdo_pgsql',
            'pgsql' => 'pdo_pgsql',
        ]))->parse($url);

        $params['application_name'] = 'mgoart-api';

        $configuration = new Configuration();

        if ($statementTimeoutMs > 0) {
            $configuration->setMiddlewares([new StatementTimeoutMiddleware($statementTimeoutMs)]);
        }

        return DriverManager::getConnection($params, $configuration);
    }
}
