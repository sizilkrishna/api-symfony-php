<?php

declare(strict_types=1);

namespace App\Database;

use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Minimal forward-only SQL migrator: applies migrations/*.sql in filename order,
 * each in its own transaction, guarded by an advisory lock.
 */
final class Migrator
{
    private const LOCK_KEY = 727274101;

    public function __construct(
        private readonly Connection $db,
        #[Autowire('%kernel.project_dir%/migrations')]
        private readonly string $directory,
    ) {
    }

    /**
     * @return list<string> versions applied (or that would be applied on a dry run)
     */
    public function migrate(bool $dryRun = false): array
    {
        $this->db->executeStatement('SET statement_timeout = 0');
        $this->db->fetchOne('SELECT pg_advisory_lock(' . self::LOCK_KEY . ')');

        try {
            $this->db->executeStatement(
                'CREATE TABLE IF NOT EXISTS schema_migrations ('
                . 'version VARCHAR(255) PRIMARY KEY, applied_at TIMESTAMPTZ NOT NULL DEFAULT now())',
            );

            $applied = $this->db->fetchFirstColumn('SELECT version FROM schema_migrations');
            $done = [];

            foreach ($this->files() as $version => $path) {
                if (\in_array($version, $applied, true)) {
                    continue;
                }

                if (!$dryRun) {
                    $this->apply($version, $path);
                }

                $done[] = $version;
            }

            return $done;
        } finally {
            $this->db->fetchOne('SELECT pg_advisory_unlock(' . self::LOCK_KEY . ')');
        }
    }

    private function apply(string $version, string $path): void
    {
        $sql = file_get_contents($path);

        if ($sql === false) {
            throw new \RuntimeException(sprintf('Cannot read migration "%s".', $path));
        }

        $this->db->beginTransaction();

        try {
            $this->db->executeStatement($sql);
            $this->db->executeStatement('INSERT INTO schema_migrations (version) VALUES (:version)', ['version' => $version]);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();

            throw new \RuntimeException(sprintf('Migration "%s" failed: %s', $version, $e->getMessage()), 0, $e);
        }
    }

    /**
     * @return array<string, string> version => path
     */
    private function files(): array
    {
        $files = glob($this->directory . '/*.sql') ?: [];
        sort($files);

        $result = [];
        foreach ($files as $file) {
            $result[basename($file, '.sql')] = $file;
        }

        return $result;
    }
}
