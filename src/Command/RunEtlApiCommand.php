<?php

namespace App\Command;

use App\Service\Etl\Dto\EtlContext;
use App\Service\Etl\Enum\ImportScope;
use App\Service\ETLService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:run-etl-api',
    description: 'Import data from an API into the database',
)]
class RunEtlApiCommand extends Command
{
    public function __construct(private readonly ETLService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('indicator', InputArgument::OPTIONAL, 'Indicator ID (e.g. 1.2.1). Omit with --all to run every supported indicator.')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Run every supported indicator.')
            ->addOption('scope', null, InputOption::VALUE_REQUIRED, 'Comma-separated scopes: municipality,comarca,province,aggregation (default: all)')
            ->addOption('csv', null, InputOption::VALUE_REQUIRED, 'Path to CSV file (for CSV-based importers).')
            ->addOption('csv2', null, InputOption::VALUE_REQUIRED, 'Second CSV path (Csv7_1_1Importer only).')
            ->addOption('skip-aggregation', null, InputOption::VALUE_NONE, 'Skip the post-import AggregationCalculatorService run (debugging).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '1024M');

        $io = new SymfonyStyle($input, $output);

        $scopeOption = $input->getOption('scope');
        $scopes = $scopeOption ? ImportScope::fromCsv($scopeOption) : null;

        $context = new EtlContext(
            triggerType: 'cli',
            identifier: 'console:'.(getenv('USER') ?: get_current_user()),
            scopes: $scopes,
            csvFilename: $input->getOption('csv'),
            csvFilename2: $input->getOption('csv2'),
            skipAggregation: (bool) $input->getOption('skip-aggregation'),
        );

        if ($input->getOption('all')) {
            $ids = $this->service->getAllSupportedIndicatorIds();
        } elseif ($input->getArgument('indicator')) {
            $ids = [$input->getArgument('indicator')];
        } else {
            $io->error('Pass an indicator ID or use --all.');

            return Command::FAILURE;
        }

        $failed = [];
        foreach ($ids as $id) {
            $ok = $this->service->run($id, $context);
            if (!$ok) {
                $failed[] = $id;
            }
        }

        if ($failed) {
            $io->warning('Failed indicators: '.implode(', ', $failed));

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
