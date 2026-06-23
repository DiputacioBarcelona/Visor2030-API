<?php

namespace App\Command;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:sync-from-prod',
    description: 'Sync `indicator` and `label` editable fields from DIBA production into the current database.',
)]
class SyncFromProdCommand extends Command
{
    private const EDITABLE_INDICATOR_FIELDS = ['weight', 'dimension_weight', 'calculation', 'sign'];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly Connection $db,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('prod-url', null, InputOption::VALUE_REQUIRED,
                'Base URL of DIBA production API',
                'https://visor2030-api.diba.cat')
            ->addOption('languages', null, InputOption::VALUE_REQUIRED,
                'Comma-separated list of languages to sync from labels-hierarchy',
                'ca,es,en')
            ->addOption('only', null, InputOption::VALUE_REQUIRED,
                'Limit to: indicators | labels | all',
                'all')
            ->addOption('write-sql', null, InputOption::VALUE_REQUIRED,
                'Write portable UPDATE statements to this .sql file (does not execute them).')
            ->addOption('apply', null, InputOption::VALUE_NONE,
                'Apply the UPDATEs to the current database. Without --apply and without --write-sql, the command runs as dry-run.')
            ->addOption('interactive', 'i', InputOption::VALUE_NONE,
                'For each difference, prompt to choose Prod / Local / Edit / Skip (recommended for labels, where DIBA prod is NOT always the source of truth).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $prodUrl     = rtrim((string) $input->getOption('prod-url'), '/');
        $languages   = array_values(array_filter(array_map('trim', explode(',', (string) $input->getOption('languages')))));
        $only        = (string) $input->getOption('only');
        $sqlFile     = $input->getOption('write-sql');
        $apply       = (bool) $input->getOption('apply');
        $interactive = (bool) $input->getOption('interactive');
        $dryRun      = !$apply && !$sqlFile;

        if (!in_array($only, ['all', 'indicators', 'labels'], true)) {
            $io->error("Valor de --only no vàlid: $only. Opcions: all | indicators | labels.");
            return Command::INVALID;
        }

        $io->title('app:sync-from-prod');
        $io->writeln("<comment>Origen:</comment> $prodUrl");
        $io->writeln('<comment>Mode:</comment> ' . ($dryRun ? 'dry-run' : ($apply ? 'apply' : 'write-sql')));
        $io->newLine();

        $diffs = [];

        if (in_array($only, ['all', 'indicators'], true)) {
            $io->section('Comparant indicadors');
            $diffs = array_merge($diffs, $this->diffIndicators($prodUrl, $io));
        }

        if (in_array($only, ['all', 'labels'], true)) {
            $io->section('Comparant etiquetes');
            $diffs = array_merge($diffs, $this->diffLabels($prodUrl, $languages, $io));
        }

        if ($diffs === []) {
            $io->success('Tot sincronitzat — cap diferència detectada.');
            return Command::SUCCESS;
        }

        $this->renderReport($io, $diffs);
        $io->note(sprintf('%d diferències detectades.', count($diffs)));

        if ($dryRun && !$interactive) {
            $io->comment('Mode dry-run; cap canvi aplicat. Usa --apply, --write-sql=<fitxer> o --interactive.');
            return Command::SUCCESS;
        }

        if ($interactive) {
            $diffs = $this->resolveInteractively($io, $diffs);
            if ($diffs === []) {
                $io->warning('Cap diferència seleccionada per aplicar. Sortint.');
                return Command::SUCCESS;
            }
            if ($dryRun) {
                $io->note(sprintf('%d canvis resolts. Afegeix --apply o --write-sql=<fitxer> per persistir-los.', count($diffs)));
                return Command::SUCCESS;
            }
        }

        if ($sqlFile) {
            $header = "-- Sync from DIBA prod generat el " . date('c') . "\n"
                    . "-- Origen: $prodUrl\n"
                    . "-- Total: " . count($diffs) . " UPDATEs (claus naturals; portable entre entorns)\n\n";
            $body = implode("\n", array_map(fn(array $d) => $this->buildUpdateSql($d), $diffs)) . "\n";
            file_put_contents($sqlFile, $header . $body);
            $io->success(sprintf('Escrites %d sentències a %s', count($diffs), $sqlFile));
        }

        if ($apply) {
            $this->db->beginTransaction();
            try {
                foreach ($diffs as $d) {
                    $this->db->update($d['table'], [$d['field'] => $d['remote']], $this->whereCriteria($d));
                }
                $this->db->commit();
                $io->success(sprintf('Aplicats %d UPDATEs a la BBDD.', count($diffs)));
            } catch (\Throwable $e) {
                $this->db->rollBack();
                $io->error('Error aplicant updates: ' . $e->getMessage());
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function diffIndicators(string $prodUrl, SymfonyStyle $io): array
    {
        $response = $this->httpClient->request('GET', "$prodUrl/api/indicators", [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $body = $response->toArray();
        // API Platform 4 exposes plain `member` (no `hydra:` prefix). Keep a fallback for older versions.
        $remoteRows = $body['member'] ?? $body['hydra:member'] ?? [];

        $localRows = $this->db->fetchAllAssociative(
            'SELECT indicator_id, weight, dimension_weight, calculation, sign FROM indicator'
        );
        $localByCode = [];
        foreach ($localRows as $row) {
            $localByCode[$row['indicator_id']] = $row;
        }

        $diffs = [];
        foreach ($remoteRows as $remote) {
            $code = $remote['indicator_id'] ?? null;
            if ($code === null) {
                continue;
            }
            if (!isset($localByCode[$code])) {
                $io->warning("Indicador $code existeix a prod però no en local — omès.");
                continue;
            }
            $local = $localByCode[$code];
            foreach (self::EDITABLE_INDICATOR_FIELDS as $field) {
                if (!array_key_exists($field, $remote)) {
                    continue;
                }
                $remoteVal = $remote[$field];
                $localVal  = $local[$field];
                if ($this->normalize($field, $remoteVal) === $this->normalize($field, $localVal)) {
                    continue;
                }
                $diffs[] = [
                    'table'        => 'indicator',
                    'indicator_id' => $code,
                    'field'        => $field,
                    'local'        => $localVal,
                    'remote'       => $remoteVal,
                ];
            }
        }

        $io->writeln(sprintf('  %d indicadors remots · %d locals · %d diferències',
            count($remoteRows), count($localRows), count($diffs)));

        return $diffs;
    }

    /**
     * @param list<string> $languages
     * @return list<array<string, mixed>>
     */
    private function diffLabels(string $prodUrl, array $languages, SymfonyStyle $io): array
    {
        if ($languages === []) {
            return [];
        }

        $localRows = $this->db->fetchAllAssociative(
            'SELECT code, language, text FROM label WHERE language IN (?)',
            [$languages],
            [ArrayParameterType::STRING],
        );
        $localByKey = [];
        foreach ($localRows as $row) {
            $localByKey[$row['language'] . '|' . $row['code']] = $row['text'];
        }

        $diffs = [];
        foreach ($languages as $lang) {
            $response = $this->httpClient->request('GET', "$prodUrl/api/labels-hierarchy", [
                'query' => ['language' => $lang],
            ]);
            $tree = $response->toArray();
            // L'endpoint retorna `[]` (llista buida) quan no hi ha labels per a la llengua.
            $flat = is_array($tree) ? $this->flattenLabelTree($tree) : [];

            $beforeCount = count($diffs);
            foreach ($flat as $code => $remoteText) {
                $key = "$lang|$code";
                if (!array_key_exists($key, $localByKey)) {
                    $io->warning("Etiqueta $lang/$code existeix a prod però no en local — omesa.");
                    continue;
                }
                if ((string) $localByKey[$key] === (string) $remoteText) {
                    continue;
                }
                $diffs[] = [
                    'table'    => 'label',
                    'code'     => $code,
                    'language' => $lang,
                    'field'    => 'text',
                    'local'    => $localByKey[$key],
                    'remote'   => $remoteText,
                ];
            }
            $io->writeln(sprintf('  [%s] %d etiquetes remotes · %d diferències',
                $lang, count($flat), count($diffs) - $beforeCount));
        }

        return $diffs;
    }

    /**
     * Aplana un arbre jeràrquic de labels a un mapa codi→text.
     * Exemple: { SDGS: { 1: { TITLE: "x" } } } ⇒ ["SDGS.1.TITLE" => "x"]
     * Els nodes interns són arrays; les fulles són escalars (string, int, bool, null).
     *
     * @param array<int|string, mixed> $tree
     * @return array<string, mixed>
     */
    private function flattenLabelTree(array $tree, string $prefix = ''): array
    {
        $flat = [];
        foreach ($tree as $k => $v) {
            $code = $prefix === '' ? (string) $k : "$prefix.$k";
            if (is_array($v)) {
                $flat += $this->flattenLabelTree($v, $code);
            } else {
                $flat[$code] = $v;
            }
        }
        return $flat;
    }

    private function normalize(string $field, mixed $value): string
    {
        if ($field === 'sign') {
            return ((bool) $value) ? '1' : '0';
        }
        if ($value === null) {
            return '';
        }
        return (string) $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function whereCriteria(array $diff): array
    {
        return $diff['table'] === 'indicator'
            ? ['indicator_id' => $diff['indicator_id']]
            : ['code' => $diff['code'], 'language' => $diff['language']];
    }

    private function buildUpdateSql(array $d): string
    {
        $value = $this->sqlLiteral($d['remote']);
        $where = $d['table'] === 'indicator'
            ? sprintf('indicator_id = %s', $this->db->quote($d['indicator_id']))
            : sprintf('code = %s AND language = %s', $this->db->quote($d['code']), $this->db->quote($d['language']));
        $comment = $d['table'] === 'indicator'
            ? $d['indicator_id']
            : $d['language'] . '/' . $d['code'];

        return sprintf('UPDATE %s SET %s = %s WHERE %s;  -- %s',
            $d['table'], $d['field'], $value, $where, $comment);
    }

    private function sqlLiteral(mixed $value): string
    {
        return match (true) {
            $value === null    => 'NULL',
            is_bool($value)    => $value ? '1' : '0',
            is_int($value), is_float($value) => (string) $value,
            default            => $this->db->quote((string) $value),
        };
    }

    private function renderReport(SymfonyStyle $io, array $diffs): void
    {
        $rows = array_map(
            fn(array $d) => [
                $d['table'],
                $d['table'] === 'indicator' ? $d['indicator_id'] : $d['language'] . '/' . $d['code'],
                $d['field'],
                $this->truncate($this->displayValue($d['field'], $d['local'])),
                $this->truncate($this->displayValue($d['field'], $d['remote'])),
            ],
            $diffs
        );
        $io->table(['Taula', 'Clau', 'Camp', 'Local (actual)', 'Prod (vol)'], $rows);
    }

    /**
     * Itera per cada diferència i pregunta a l'usuari quina versió ha de prevaler.
     * Retorna només les diferències que generaran un UPDATE (descarta Local/Skip,
     * substitueix `remote` pel valor escollit quan és Prod/Edit).
     *
     * @param list<array<string, mixed>> $diffs
     * @return list<array<string, mixed>>
     */
    private function resolveInteractively(SymfonyStyle $io, array $diffs): array
    {
        $io->section('Revisió interactiva');
        $io->writeln('Per a cada diferència trieu: [<info>P</info>]rod, [<info>L</info>]ocal, [<info>E</info>]dit, [<info>S</info>]kip, [<info>Q</info>]uit');
        $io->newLine();

        $resolved = [];
        $counts = ['P' => 0, 'L' => 0, 'E' => 0, 'S' => 0];
        $total = count($diffs);

        foreach ($diffs as $i => $d) {
            $header = sprintf('[%d/%d] %s · %s · camp <comment>%s</comment>',
                $i + 1, $total, $d['table'],
                $d['table'] === 'indicator' ? $d['indicator_id'] : $d['language'] . '/' . $d['code'],
                $d['field']
            );
            $io->writeln($header);
            $io->writeln("  <fg=cyan>[L] Local (Heroku/actual):</> " . $this->formatValueForReview($d['field'], $d['local']));
            $io->writeln("  <fg=magenta>[P] Prod (DIBA):</>           " . $this->formatValueForReview($d['field'], $d['remote']));

            $choice = strtoupper((string) $io->ask(
                'Triï P/L/E/S/Q',
                'P',
                fn ($v) => in_array(strtoupper((string) $v), ['P', 'L', 'E', 'S', 'Q'], true)
                    ? strtoupper((string) $v)
                    : throw new \InvalidArgumentException('Resposta no vàlida; usa P, L, E, S o Q.')
            ));

            if ($choice === 'Q') {
                $io->warning(sprintf('Aturat per l\'usuari al diff %d/%d.', $i + 1, $total));
                break;
            }
            if ($choice === 'L' || $choice === 'S') {
                $counts[$choice]++;
                $io->newLine();
                continue;
            }
            if ($choice === 'E') {
                $edited = $this->editInExternalEditor((string) ($d['remote'] ?? ''), $d);
                if ($edited === null) {
                    $io->warning('Edició cancel·lada; aquest diff s\'omet.');
                    $counts['S']++;
                    $io->newLine();
                    continue;
                }
                $d['remote'] = $this->castToFieldType($d['field'], $edited);
                $counts['E']++;
                $resolved[] = $d;
                $io->writeln('  → <info>Editat</info>: ' . $this->formatValueForReview($d['field'], $d['remote']));
                $io->newLine();
                continue;
            }
            // 'P' — agafem la versió de prod tal qual
            $counts['P']++;
            $resolved[] = $d;
            $io->newLine();
        }

        $io->writeln(sprintf(
            '<info>Resolts:</info> P=%d  L=%d  E=%d  S=%d  →  <comment>%d UPDATEs</comment>',
            $counts['P'], $counts['L'], $counts['E'], $counts['S'], count($resolved)
        ));
        $io->newLine();

        return $resolved;
    }

    /**
     * Obre $EDITOR (o nano per defecte) amb el valor inicial i una capçalera explicativa.
     * L'usuari edita, desa i tanca; el script llegeix el resultat (sense la capçalera).
     * Retorna null si el contingut queda buit o l'usuari deixa la sentinel intacta.
     */
    private function editInExternalEditor(string $initial, array $diff): ?string
    {
        $sentinel = '# === Escriu el text final per sota; les línies amb # es descarten ===';
        $local    = "# LOCAL (Heroku):\n# " . str_replace("\n", "\n# ", (string) ($diff['local'] ?? ''));
        $remote   = "# PROD  (DIBA):\n# "  . str_replace("\n", "\n# ", (string) ($diff['remote'] ?? ''));
        $contents = $sentinel . "\n" . $local . "\n" . $remote . "\n#\n" . $initial;

        $tmp = tempnam(sys_get_temp_dir(), 'sync-prod-');
        if ($tmp === false) {
            return null;
        }
        file_put_contents($tmp, $contents);

        $editor = (string) (getenv('EDITOR') ?: 'nano');
        $process = Process::fromShellCommandline(sprintf('%s %s', escapeshellcmd($editor), escapeshellarg($tmp)));
        $process->setTty(Process::isTtySupported());
        $process->setTimeout(null);
        $process->run();

        $result = (string) file_get_contents($tmp);
        @unlink($tmp);

        // Treiem les línies de capçalera (# …)
        $lines = preg_split('/\R/', $result) ?: [];
        $clean = implode("\n", array_filter($lines, fn (string $l) => !str_starts_with(ltrim($l), '#')));
        $clean = trim($clean, "\n");

        return $clean === '' ? null : $clean;
    }

    /**
     * Converteix el text editat al tipus correcte segons el camp (sign → bool, weight → int, …).
     */
    private function castToFieldType(string $field, string $raw): mixed
    {
        return match ($field) {
            'sign'                                => in_array(strtolower(trim($raw)), ['1', 'true', 'yes', 'si', 'sí'], true),
            'weight', 'dimension_weight'          => (int) trim($raw),
            default                               => $raw,
        };
    }

    private function formatValueForReview(string $field, mixed $value): string
    {
        if ($value === null) {
            return '<fg=gray>NULL</>';
        }
        if ($field === 'sign') {
            return ((bool) $value) ? 'true' : 'false';
        }
        $s = (string) $value;
        // Si és multilínia, posem-ho a sota indentat
        if (str_contains($s, "\n") || mb_strlen($s) > 100) {
            return "\n    " . str_replace("\n", "\n    ", $s);
        }
        return $s;
    }

    private function displayValue(string $field, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if ($field === 'sign') {
            return ((bool) $value) ? 'true' : 'false';
        }
        return (string) $value;
    }

    private function truncate(string $s, int $max = 60): string
    {
        return mb_strlen($s) > $max ? mb_substr($s, 0, $max - 1) . '…' : $s;
    }
}
