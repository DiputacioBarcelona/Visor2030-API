<?php

namespace App\Command;

use App\Config\AggregationConfig;
use App\Service\AggregationCalculatorService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:calculate-aggregation-values',
    description: 'Calculate and persist aggregated indicator values for aggregations and/or comarcas',
)]
class CalculateAggregationValuesCommand extends Command
{
    public function __construct(
        private readonly AggregationCalculatorService $calculatorService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'indicator',
                InputArgument::OPTIONAL,
                'Specific indicator_id to process (e.g. "1.2.1"); omit to process all eligible indicators',
            )
            ->addOption(
                'target',
                null,
                InputOption::VALUE_REQUIRED,
                'Target type to calculate: aggregation | comarca | province | all',
                'all',
            )
            ->addOption(
                'group',
                null,
                InputOption::VALUE_REQUIRED,
                'Specific group slug (aggregation) or comarca_code (comarca); omit to process all groups',
            )
            ->addOption(
                'strategy',
                null,
                InputOption::VALUE_REQUIRED,
                'Limit to a specific strategy: ratio | population-weighted',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '1024M');

        $io = new SymfonyStyle($input, $output);

        $targetIndicator = $input->getArgument('indicator');
        $target          = $input->getOption('target');
        $group           = $input->getOption('group');
        $strategy        = $input->getOption('strategy');

        if (!in_array($target, ['aggregation', 'comarca', 'province', 'all'], true)) {
            $io->error("Invalid --target value \"$target\". Allowed: aggregation, comarca, province, all.");
            return Command::FAILURE;
        }

        if ($strategy !== null) {
            $aggregationIndicators = AggregationConfig::getIndicatorsForStrategy($strategy);
            $comarcaIndicators     = AggregationConfig::getComarcaIndicatorsForStrategy($strategy);
            $provinceIndicators    = AggregationConfig::getProvinceIndicatorsForStrategy($strategy);

            if ($aggregationIndicators === null) {
                $io->error("Invalid --strategy value \"$strategy\". Allowed: ratio, population-weighted.");
                return Command::FAILURE;
            }
        } else {
            $aggregationIndicators = AggregationConfig::getAllEligibleIndicators();
            $comarcaIndicators     = AggregationConfig::getAllEligibleComarcaIndicators();
            $provinceIndicators    = AggregationConfig::getAllEligibleProvinceIndicators();
        }

        // Restrict to the specific indicator argument if provided
        if ($targetIndicator) {
            $aggregationIndicators = in_array($targetIndicator, $aggregationIndicators, true) ? [$targetIndicator] : [];
            $comarcaIndicators     = in_array($targetIndicator, $comarcaIndicators, true) ? [$targetIndicator] : [];
            $provinceIndicators    = in_array($targetIndicator, $provinceIndicators, true) ? [$targetIndicator] : [];
        }

        // Drop lists that are not relevant to the chosen target
        if (!in_array($target, ['aggregation', 'all'], true)) {
            $aggregationIndicators = [];
        }
        if (!in_array($target, ['comarca', 'all'], true)) {
            $comarcaIndicators = [];
        }
        if (!in_array($target, ['province', 'all'], true)) {
            $provinceIndicators = [];
        }

        if (empty($aggregationIndicators) && empty($comarcaIndicators) && empty($provinceIndicators)) {
            $io->warning('No eligible indicators to process.');
            return Command::SUCCESS;
        }

        $processed = 0;

        foreach ($aggregationIndicators as $indicatorId) {
            try {
                $this->calculatorService->calculateForAggregation($indicatorId, $group ?: null);
                $io->writeln("Calculated aggregation: $indicatorId");
                ++$processed;
            } catch (\Exception $e) {
                $io->warning("Skipped aggregation $indicatorId: " . $e->getMessage());
            }
        }

        foreach ($comarcaIndicators as $indicatorId) {
            try {
                $this->calculatorService->calculateForComarca($indicatorId, $group ?: null);
                $io->writeln("Calculated comarca: $indicatorId");
                ++$processed;
            } catch (\Exception $e) {
                $io->warning("Skipped comarca $indicatorId: " . $e->getMessage());
            }
        }

        foreach ($provinceIndicators as $indicatorId) {
            try {
                $this->calculatorService->calculateForProvince($indicatorId, $group ?: null);
                $io->writeln("Calculated province: $indicatorId");
                ++$processed;
            } catch (\Exception $e) {
                $io->warning("Skipped province $indicatorId: " . $e->getMessage());
            }
        }

        $io->success("Done. Processed $processed indicator(s).");

        return Command::SUCCESS;
    }
}
