<?php

namespace App\Service\Etl\Util;

/**
 * Fallback first-name → gender lookup for the DIBA elected-officials dataset
 * (used by DibaImporter when the `sexe` field is blank).
 *
 * Naive prefix match against a hand-maintained list of Catalan/Spanish female
 * first names. Returns 'D' (dona) on match, empty string on miss.
 */
final class NameGenderGuesser
{
    /** Hand-maintained list of female first names; matched as case/accent-insensitive prefix. */
    private const WOMEN_FIRST_NAMES = [
        'Maria', 'Anna', 'Nuria', 'Marina', 'Alba', 'Laura', 'Marta', 'Judit',
        'Cristina', 'Silvia', 'Sonia', 'Patricia', 'Gemma', 'Joana', 'Jessica',
        'Carolina', 'Ariadna', 'Isabel', 'Mercedes', 'Noa', 'Estela', 'Susana',
        'Laia', 'Zulma', 'Carla', 'Blanca', 'Elisabet', 'Naima', 'Esther',
        'Mónica', 'Rosabel', 'Misericordia', 'Rosa', 'Lluisa', 'Olaya', 'Emilia',
        'Inmaculada', 'Montserrat', 'Yolanda', 'Vanesa', 'Alessandra', 'Mireia',
        'Nora', 'Emma', 'Olga', 'Lorena', 'Ester', 'Marian',
    ];

    /** Returns 'D' if $name starts with a known female first name, '' otherwise. */
    public static function guess(string $name): string
    {
        $normalised = strtr(strtolower($name), 'áéíóúü', 'aeiouu');

        foreach (self::WOMEN_FIRST_NAMES as $woman) {
            if (str_starts_with($normalised, strtolower($woman))) {
                return 'D';
            }
        }

        return '';
    }
}
