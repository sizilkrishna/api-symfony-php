<?php

declare(strict_types=1);

namespace App\Command;

use App\Database\Migrator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:db:migrate', description: 'Apply pending SQL migrations from ./migrations')]
final class MigrateCommand extends Command
{
    public function __construct(private readonly Migrator $migrator)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'List pending migrations without applying them');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        $applied = $this->migrator->migrate($dryRun);

        if ($applied === []) {
            $io->success('Database is up to date.');

            return Command::SUCCESS;
        }

        $io->listing($applied);
        $io->success(sprintf('%d migration(s) %s.', \count($applied), $dryRun ? 'pending' : 'applied'));

        return Command::SUCCESS;
    }
}
