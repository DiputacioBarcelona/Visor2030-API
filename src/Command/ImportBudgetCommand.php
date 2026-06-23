<?php

namespace App\Command;

use App\Service\BudgetImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:import-budgets',
    description: 'Import municipal budget data (tb_inventario + tb_funcional) for a given year.',
)]
class ImportBudgetCommand extends Command
{
    public function __construct(private readonly BudgetImporter $importer)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('year', InputArgument::REQUIRED, 'The year for which to import the budget data.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '1024M');

        $year = (int) $input->getArgument('year');

        try {
            $this->importer->import($year);
            $output->writeln('<info>Budget import completed successfully!</info>');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error: '.$e->getMessage().'</error>');

            return Command::FAILURE;
        }
    }
}
