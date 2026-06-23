<?php

namespace App\Command;

use App\Service\CsvExportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:export-csv',
    description: 'Generates CSV files filtered by SDG. If no SDG is provided, generates all SDG (1-17).',
)]
class ExportCsvCommand extends Command
{
    private CsvExportService $csvExportService;

    public function __construct(CsvExportService $csvExportService)
    {
        parent::__construct();
        $this->csvExportService = $csvExportService;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('sdg', InputArgument::OPTIONAL, 'The SDG to filter by (1-17). If omitted, all 17 SDGs are generated.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        ini_set('memory_limit', '1024M');
        
        $sdgs = $input->getArgument('sdg') ? [(int) $input->getArgument('sdg')] : range(1, 17);

        foreach ($sdgs as $sdg) {
            $filePath = sprintf(__DIR__.'/../../var/sdg%d.csv', $sdg);

            try {
                $this->csvExportService->generateCsv((string) $sdg, $filePath);
                $output->writeln("<info>CSV file generated successfully: $filePath</info>");
            } catch (\Exception $e) {
                $output->writeln("<error>Error generating SDG $sdg: ".$e->getMessage().'</error>');
            }
        }

        return Command::SUCCESS;
    }
}
