<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\LogRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:logs:prune', description: 'Delete query-log rows (and the IP addresses in them) older than N days')]
final class PruneLogsCommand extends Command
{
    public function __construct(private readonly LogRepository $logs)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('days', null, InputOption::VALUE_REQUIRED, 'Retention in days', '90');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = filter_var($input->getOption('days'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($days === false) {
            $io->error('--days must be a positive integer.');

            return Command::INVALID;
        }

        $io->success(sprintf('Deleted %d log row(s) older than %d days.', $this->logs->prune($days), $days));

        return Command::SUCCESS;
    }
}
