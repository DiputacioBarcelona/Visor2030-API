<?php

namespace App\Command;

use App\Service\LoadMunicipalityDataService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-population',
    description: 'Import historical population data from Transparència Catalunya API into the population table',
)]
class ImportPopulationCommand extends Command
{
    public function __construct(
        private readonly LoadMunicipalityDataService $municipalityDataService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '1024M');

        $io = new SymfonyStyle($input, $output);
        $io->title('Importing population data from Transparència Catalunya');

        $this->municipalityDataService->importPopulation();

        $io->success('Done.');

        return Command::SUCCESS;
    }
}
