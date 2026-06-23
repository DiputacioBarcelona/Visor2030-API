<?php

namespace App\Command;

use App\Service\LoadMunicipalityDataService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:load-initial-data',
    description: 'Load data for Municipality, Comarca, Population and Aggregation entities',
)]
class LoadInitialDataCommand extends Command
{
    public function __construct(
        private readonly LoadMunicipalityDataService $municipalityService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('municipalities', null, InputOption::VALUE_NONE, 'Import municipalities, comarques and provinces')
            ->addOption('population', null, InputOption::VALUE_NONE, 'Import historical population data')
            ->addOption('ubicacio', null, InputOption::VALUE_NONE, 'Import ubicacio aggregation data')
            ->addOption('ruralitat', null, InputOption::VALUE_NONE, 'Import ruralitat aggregation data')
            ->addOption('territory', null, InputOption::VALUE_NONE, 'Import territory + AMB/RMB aggregation data')
            ->addOption('industrial', null, InputOption::VALUE_NONE, 'Import industrial aggregation data')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '1024M');

        $io = new SymfonyStyle($input, $output);

        $runAll = !$input->getOption('municipalities')
            && !$input->getOption('population')
            && !$input->getOption('ubicacio')
            && !$input->getOption('ruralitat')
            && !$input->getOption('territory')
            && !$input->getOption('industrial');

        if ($runAll || $input->getOption('municipalities')) {
            $io->section('Importing municipalities, comarques and provinces...');
            $this->municipalityService->importMunicipalities();
            $io->success('Municipalities imported.');
        }

        if ($runAll || $input->getOption('population')) {
            $io->section('Importing historical population data...');
            $this->municipalityService->importPopulation();
            $io->success('Population data imported.');
        }

        if ($runAll || $input->getOption('ubicacio')) {
            $io->section('Importing ubicacio aggregation data...');
            $this->municipalityService->importUbicacioData();
            $io->success('Ubicacio data imported.');
        }

        if ($runAll || $input->getOption('ruralitat')) {
            $io->section('Importing ruralitat aggregation data...');
            $this->municipalityService->importRuralitatData();
            $io->success('Ruralitat data imported.');
        }

        if ($runAll || $input->getOption('territory')) {
            $io->section('Importing territory + AMB/RMB aggregation data...');
            $result = $this->municipalityService->importTerritoryData();
            $io->success(sprintf('Territory data imported: %d municipalities processed, %d skipped.', $result['imported'], $result['skipped']));
        }

        if ($runAll || $input->getOption('industrial')) {
            $io->section('Importing industrial aggregation data...');
            $this->municipalityService->importIndustrialData();
            $io->success('Industrial data imported.');
        }

        $io->success('All done.');

        return Command::SUCCESS;
    }
}
