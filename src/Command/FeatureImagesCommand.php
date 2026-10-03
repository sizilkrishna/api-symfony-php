<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:db:feature-images', description: 'Pick feature images for taxonomy rows that have none')]
final class FeatureImagesCommand extends Command
{
    public function __construct(private readonly Connection $db)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->db->executeStatement('SET statement_timeout = 0');
        $this->db->fetchOne('SELECT update_feature_images()');

        (new SymfonyStyle($input, $output))->success('Feature images updated.');

        return Command::SUCCESS;
    }
}
