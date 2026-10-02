<?php

namespace App\Service;

use PDO;
use PDOException;

class DatabaseService
{
    private ?PDO $pdo = null;

    public function __construct(
        private readonly string $dsn,
        private readonly string $user,
        private readonly string $password,
    ) {
    }

    public function getConnection(): PDO
    {
        if ($this->pdo === null) {
            try {
                $this->pdo = new PDO(
                    $this->dsn,
                    $this->user,
                    $this->password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } catch (PDOException $e) {
                throw new PDOException('Database connection failed: ' . $e->getMessage());
            }
        }

        return $this->pdo;
    }

    public function executeQuery(string $sql, array $params = []): array
    {
        $statement = $this->getConnection()->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll() ?: [];
    }

    public function executeCount(string $sql, array $params = []): int
    {
        $statement = $this->getConnection()->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }
}
