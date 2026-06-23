<?php

namespace App\Service\Etl\Persistence;

/**
 * Disposition returned by ValuePersister::set*Value methods, used by AbstractEtlImporter
 * to drive the per-run counters that appear in the terminal summary.
 */
enum ValueWriteResult: string
{
    case Created   = 'created';
    case Updated   = 'updated';
    case Unchanged = 'unchanged';
    case Skipped   = 'skipped';
}
