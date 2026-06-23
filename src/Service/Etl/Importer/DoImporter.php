<?php

namespace App\Service\Etl\Importer;

use App\Entity\Indicator;
use App\Service\Etl\Dto\EtlContext;
use App\Service\Etl\Dto\IndicatorDefinition;
use App\Service\Etl\Enum\ImportScope;

/**
 * Handles indicators sourced from the Transparència Catalunya open-data API
 * (analisi.transparenciacatalunya.cat).
 *
 * NOTE: indicator 11.4.1 is mapped to 'DO' in IDS.php but has no working
 * definition — it was never imported and is not included here.
 */
final class DoImporter extends AbstractEtlImporter
{
    protected function getDefinitions(): array
    {
        return [
            '2.1.1' => new IndicatorDefinition(
                indicatorId: '2.1.1',
                targetId: '2.1',
                targetName: "Fomentar la productivitat agrícola i els ingressos de les persones que es dediquen a la producció d'aliments a petita escala.",
                sdg: 2,
                indicatorName: '% Superficie agricola ecològica',
                indicatorDescription: 'Superfície agrícola ecològica en relació a la superfície agrícola total',
                sign: true,
                source: 'DO',
                unit: 'ha',
                scale: 1,
                url: 'https://analisi.transparenciacatalunya.cat/resource/si4p-ygat.json?$query=SELECT%0A%20%20%60id_mun%60%2C%0A%20%20%60ccpae_e%60%2C%0A%20%20%60campanya%60%2C%0A%20%20sum(%60sup_neta_h%60)%20AS%20%60sum_sup_neta_h%60%2C%0A%20%20%60nom_mun%60%0AWHERE%0A%20%20caseless_one_of(%60nom_mun%60%2C%20%22BARCELONA%22)%20AND%20caseless_eq(%60pro%60%2C%20%2208%22)%0AGROUP%20BY%20%60id_mun%60%2C%20%60ccpae_e%60%2C%20%60campanya%60%2C%20%60nom_mun%60%0AHAVING%20caseless_one_of(%60campanya%60%2C%20%22[[[year]]]%22)',
                urlInfo: 'https://analisi.transparenciacatalunya.cat/resource/si4p-ygat.json?$query=SELECT%20%60campanya%60%20GROUP%20BY%20%60campanya%60%20ORDER%20BY%20%60campanya%60%20DESC%20NULL%20LAST',
                extra: ['import' => 'aggregated'],
            ),
            '8.3.2' => new IndicatorDefinition(
                indicatorId: '8.3.2',
                targetId: '8.3',
                targetName: "Promoure la creació d'ocupació digna a través de la innovació, creativitat i emprenedoria, fomentant el creixement de les petites i mitjanes empreses.",
                sdg: 8,
                indicatorName: 'Est. comercials per 1.000 hab.',
                indicatorDescription: "Nombre d'establiments comercials per cada 1.000 habitants",
                sign: true,
                source: 'DO',
                unit: 'establiments',
                scale: 1000,
                url: 'https://analisi.transparenciacatalunya.cat/resource/2dhj-q3r8.json?$query=SELECT%0A%20%20%60municipi%60%2C%0A%20%20%60any%60%2C%0A%20%20%60poblaci%60%2C%0A%20%20%60establiments%60%2C%0A%20%20%60densitat_comercial_est_1%60%0AWHERE%0A%20%20(%60any%60%20IN%20(%22[[[year]]]%22))%0A%20%20AND%20caseless_one_of(%0A%20%20%20%20%60mbit_territorial%60%2C%0A%20%20%20%20%22Metropolit%C3%A0%22%2C%0A%20%20%20%20%22Pened%C3%A8s%22%2C%0A%20%20%20%20%22Comarques%20Centrals%22%0A%20%20)',
                urlInfo: 'https://analisi.transparenciacatalunya.cat/resource/2dhj-q3r8.json?$query=SELECT%20%60any%60%20GROUP%20BY%20%60any%60%20ORDER%20BY%20%60any%60%20DESC%20NULL%20LAST',
                extra: ['import' => 'simple', 'munBy' => 'name', 'munField' => 'municipi', 'field1' => 'establiments', 'field2' => 'poblaci'],
            ),
            '8.3.4' => new IndicatorDefinition(
                indicatorId: '8.3.4',
                targetId: '8.3',
                targetName: "Promoure la creació d'ocupació digna a través de la innovació, creativitat i emprenedoria, fomentant el creixement de les petites i mitjanes empreses.",
                sdg: 8,
                indicatorName: '% contractació pública a PIMES',
                indicatorDescription: '% contractació pública a PIMES',
                sign: true,
                source: 'DO',
                unit: '',
                scale: 1,
                url: 'https://analisi.transparenciacatalunya.cat/resource/ybgg-dgi6.json?$query=SELECT%0A%20%20%60codi_ine10%60%2C%0A%20%20%60tipus_empresa%60%2C%0A%20%20count(%60tipus_empresa%60)%20AS%20%60count_tipus_empresa%60%2C%0A%20%20date_trunc_y(%60data_publicacio_formalitzacio%60)%20AS%20%60by_year_data_publicacio_formalitzacio%60%0AWHERE%0A%20%20caseless_starts_with(%60codi_ine10%60%2C%20%2208%22)%0A%20%20AND%20((%60tipus_empresa%60%20IS%20NOT%20NULL)%0A%20%20%20%20%20%20%20%20%20AND%20(caseless_one_of(%60resultat%60%2C%20%22Formalitzaci%C3%B3%22)%0A%20%20%20%20%20%20%20%20%20%20%20%20%20%20%20%20AND%20(%60data_publicacio_formalitzacio%60%20IS%20NOT%20NULL)))%0AGROUP%20BY%0A%20%20%60codi_ine10%60%2C%0A%20%20%60tipus_empresa%60%2C%0A%20%20date_trunc_y(%60data_publicacio_formalitzacio%60)%0AHAVING%0A%20%20%60by_year_data_publicacio_formalitzacio%60%0A%20%20%20%20BETWEEN%20%22[[[year]]]-01-01T00%3A00%3A00%22%20%3A%3A%20floating_timestamp%0A%20%20%20%20AND%20%22[[[year]]]-12-31T23%3A59%3A59%22%20%3A%3A%20floating_timestamp',
                urlInfo: 'https://analisi.transparenciacatalunya.cat/resource/ybgg-dgi6.json?$query=SELECT%0A%20%20date_trunc_y(%60data_publicacio_formalitzacio%60)%20AS%20%60by_year_data_publicacio_formalitzacio%60%0AWHERE%0A%20%20caseless_starts_with(%60codi_ine10%60%2C%20%2208%22)%0A%20%20AND%20((%60tipus_empresa%60%20IS%20NOT%20NULL)%0A%20%20%20%20%20%20%20%20%20AND%20(caseless_one_of(%60resultat%60%2C%20%22Formalitzaci%C3%B3%22)%0A%20%20%20%20%20%20%20%20%20%20%20%20%20%20%20%20AND%20(%60data_publicacio_formalitzacio%60%20IS%20NOT%20NULL)))%0AGROUP%20BY%20date_trunc_y(%60data_publicacio_formalitzacio%60)',
                extra: ['import' => 'combined'],
            ),
            '11.3.1' => new IndicatorDefinition(
                indicatorId: '11.3.1',
                targetId: '11.3',
                targetName: "Aconseguir un model urbà inclusiu i sostenible a través d'una planificació estratègica concertada amb el territori i amb la participació de la ciutadania.",
                sdg: 11,
                indicatorName: 'Percentatge de sòl artificialitztat',
                indicatorDescription: 'Percentatge de sòl artificialitzat (real o potencial). Percentatge de sòl destinat a usos urbans o infraestructures respecte la superfície total de la unitat territorial',
                sign: true,
                source: 'DO',
                unit: 'ha',
                scale: 1,
                url: 'https://analisi.transparenciacatalunya.cat/resource/epsm-zskb.json?$query=SELECT%0A%20%20%60any%60%2C%0A%20%20%60codi_ine_5_txt%60%2C%0A%20%20%60superficie_ha%60%2C%0A%20%20%60_05_su%60%2C%0A%20%20%60_05_perce_su%60%0AWHERE%0A%20%20caseless_starts_with(%60codi_ine_5_txt%60%2C%20%2208%22)%20AND%20(%60any%60%20IN%20(%22[[[year]]]%22))',
                urlInfo: 'https://analisi.transparenciacatalunya.cat/resource/epsm-zskb.json?$query=SELECT%20%60any%60%20GROUP%20BY%20%60any%60%20ORDER%20BY%20%60any%60%20DESC%20NULL%20LAST',
                extra: ['import' => 'simple', 'munBy' => 'code', 'munField' => 'codi_ine_5_txt', 'field1' => '_05_su', 'field2' => 'superficie_ha'],
            ),
            '11.3.2' => new IndicatorDefinition(
                indicatorId: '11.3.2',
                targetId: '11.3',
                targetName: "Aconseguir un model urbà inclusiu i sostenible a través d'una planificació estratègica concertada amb el territori i amb la participació de la ciutadania.",
                sdg: 11,
                indicatorName: 'Equipaments urbans per habitant',
                indicatorDescription: 'Equipaments urbans per habitant',
                sign: true,
                source: 'DO',
                unit: 'm2/hab',
                scale: 1,
                url: 'https://analisi.transparenciacatalunya.cat/resource/b9cr-32i4.json?$query=SELECT%20%60codi_ine_c%60%2C%20%60any%60%2C%20%60i_07%60%0AWHERE%20(%60any%60%20IN%20(%22[[[year]]]%22))%20AND%20caseless_starts_with(%60codi_ine_c%60%2C%20%2208%22)',
                urlInfo: 'https://analisi.transparenciacatalunya.cat/resource/b9cr-32i4.json?$query=SELECT%20%60any%60%0AWHERE%20caseless_starts_with(%60codi_ine_c%60%2C%20%2208%22)%0AGROUP%20BY%20%60any%60%0AORDER%20BY%20%60any%60%20DESC%20NULL%20LAST',
                extra: ['import' => 'simple', 'munBy' => 'code', 'munField' => 'codi_ine_c', 'field1' => 'i_07', 'field2' => null],
            ),
            '11.7.1' => new IndicatorDefinition(
                indicatorId: '11.7.1',
                targetId: '11.7',
                targetName: 'Proporcionar accés universal a més zones verdes i espais públics segurs, inclusius i accessibles, amb especial èmfasi a les dones, els infants, les persones grans i les persones amb discapacitat.',
                sdg: 11,
                indicatorName: 'Superfície (m2) zona verda',
                indicatorDescription: 'Relació entre volum de població municipal i espai qualificat de zona verda o espai lliure en el sòl urbà del municipi',
                sign: true,
                source: 'DO',
                unit: 'm2',
                scale: 1,
                url: 'https://analisi.transparenciacatalunya.cat/resource/epsm-zskb.json?$query=SELECT%0A%20%20%60any%60%2C%0A%20%20%60codi_ine_5_txt%60%2C%0A%20%20%60superficie_ha%60%2C%0A%20%20%60poblacio_padro%60%2C%0A%20%20%60_19_zverdes_habt%60%0AWHERE%0A%20%20(%60any%60%20IN%20(%22[[[year]]]%22))%20AND%20caseless_starts_with(%60codi_ine_5_txt%60%2C%20%2208%22)',
                urlInfo: 'https://analisi.transparenciacatalunya.cat/resource/epsm-zskb.json?$query=SELECT%20%60any%60%20GROUP%20BY%20%60any%60%20ORDER%20BY%20%60any%60%20DESC%20NULL%20LAST',
                // value is denormalized: stored as field1 × field2 (total m2, not m2/hab)
                extra: ['import' => 'simple', 'munBy' => 'code', 'munField' => 'codi_ine_5_txt', 'field1' => '_19_zverdes_habt', 'field2' => 'poblacio_padro', 'denormalize' => true],
            ),
            '12.5.1' => new IndicatorDefinition(
                indicatorId: '12.5.1',
                targetId: '12.5',
                targetName: 'Disminuir substancialment la generació de residus mitjançant polítiques de prevenció, reducció, reciclatge i reutilització.',
                sdg: 12,
                indicatorName: 'Taxa recollida selectiva',
                indicatorDescription: 'Percentatge de recollida selectiva de residus domèstics respecte del total produït',
                sign: true,
                source: 'DO',
                unit: 'tm',
                scale: 1,
                url: 'https://analisi.transparenciacatalunya.cat/resource/69zu-w48s.json?$query=SELECT%0A%20%20%60any%60%2C%0A%20%20%60codi_municipi%60%2C%0A%20%20%60r_s_r_m_total%60%2C%0A%20%20%60total_recollida_selectiva%60%2C%0A%20%20%60generaci_residus_municipal%60%0AWHERE%0A%20%20caseless_one_of(%60any%60%2C%20%22[[[year]]]%22)%0A%20%20AND%20caseless_starts_with(%60codi_municipi%60%2C%20%228%22)',
                urlInfo: 'https://analisi.transparenciacatalunya.cat/resource/69zu-w48s.json?$query=SELECT%20%60any%60%20GROUP%20BY%20%60any%60%20ORDER%20BY%20%60any%60%20DESC%20NULL%20LAST',
                extra: ['import' => 'simple', 'munBy' => 'code', 'munField' => 'codi_municipi', 'field1' => 'total_recollida_selectiva', 'field2' => 'generaci_residus_municipal'],
            ),
            '12.5.2' => new IndicatorDefinition(
                indicatorId: '12.5.2',
                targetId: '12.5',
                targetName: 'Disminuir substancialment la generació de residus mitjançant polítiques de prevenció, reducció, reciclatge i reutilització.',
                sdg: 12,
                indicatorName: 'Residus domèstics en kg/hab/any',
                indicatorDescription: 'Producció de residus domèstics en kg/hab/any',
                sign: true,
                source: 'DO',
                unit: 'kg',
                scale: 1000,
                url: 'https://analisi.transparenciacatalunya.cat/resource/69zu-w48s.json?$query=SELECT%0A%20%20%60any%60%2C%0A%20%20%60codi_municipi%60%2C%0A%20%20%60generaci_residus_municipal%60%2C%0A%20%20%60poblaci%60%2C%0A%20%20%60kg_hab_any%60%0AWHERE%0A%20%20caseless_one_of(%60any%60%2C%20%22[[[year]]]%22)%0A%20%20AND%20caseless_starts_with(%60codi_municipi%60%2C%20%228%22)%20limit%208000',
                urlInfo: 'https://analisi.transparenciacatalunya.cat/resource/69zu-w48s.json?$query=SELECT%20%60any%60%20GROUP%20BY%20%60any%60%20ORDER%20BY%20%60any%60%20DESC%20NULL%20LAST',
                extra: ['import' => 'simple', 'munBy' => 'code', 'munField' => 'codi_municipi', 'field1' => 'generaci_residus_municipal', 'field2' => 'poblaci'],
            ),
            '15.4.1' => new IndicatorDefinition(
                indicatorId: '15.4.1',
                targetId: '15.4',
                targetName: 'Vetllar per la conservació dels ecosistemes muntanyosos, en particular, de la seva diversitat biològica.',
                sdg: 15,
                indicatorName: 'Superfície en Ha del sòl no urbanitzable.',
                indicatorDescription: 'Superfície en Ha del sòl no urbanitzable. Qualificació del MUC en el sòl no urbanitzable (N2: Protecció)',
                sign: true,
                source: 'DO',
                unit: 'percent',
                scale: 1,
                url: 'https://analisi.transparenciacatalunya.cat/resource/epsm-zskb.json?$query=SELECT%20%60any%60%2C%20%60codi_ine_6_txt%60%2C%20%60superficie_ha%60%2C%20%60_16_n2_snu%60%0AWHERE%20%60any%60%20IN%20(%22[[[year]]]%22)',
                urlInfo: 'https://analisi.transparenciacatalunya.cat/resource/epsm-zskb.json?$query=SELECT%20%60any%60%20GROUP%20BY%20%60any%60%20ORDER%20BY%20%60any%60%20DESC%20NULL%20LAST',
                extra: ['import' => 'simple', 'munBy' => 'code', 'munField' => 'codi_ine_6_txt', 'field1' => '_16_n2_snu', 'field2' => 'superficie_ha'],
            ),
            '16.7.1' => new IndicatorDefinition(
                indicatorId: '16.7.1',
                targetId: '16.7',
                targetName: "Garantir l'adopció de decisions inclusives, participatives i representatives que responguin a les necessitats de la ciutadania.",
                sdg: 16,
                indicatorName: 'Participació eleccions municipals',
                indicatorDescription: "Nombre d'electors que realment exerceixen el seu dret de vot. S'expressa en el percentatge del nombre de votants (inclosos els vots en blanc i els nuls) respecte al nombre total d'electors.",
                sign: true,
                source: 'DO',
                unit: 'percent',
                scale: 1,
                url: 'https://analisi.transparenciacatalunya.cat/resource/irrv-2mfc.json?$query=SELECT%0A%20%20%60id_eleccio%60%2C%0A%20%20%60territori_codi%60%2C%0A%20%20sum(%60votants%60)%20AS%20%60sum_votants%60%2C%0A%20%20sum(%60padro%60)%20AS%20%60sum_padro%60%2C%0A%20%20sum(%60cens_electoral%60)%20AS%20%60sum_cens_electoral%60%0AWHERE%0A%20%20caseless_one_of(%60id_eleccio%60%2C%20%22[[[year]]]%22)%0A%20%20AND%20caseless_contains(%60nom_eleccio%60%2C%20%22Eleccions%20Municipals%22)%0A%20%20AND%20(caseless_one_of(%60id_nivell_territorial%60%2C%20%22MU%22)%0A%20%20%20%20%20%20%20%20%20AND%20caseless_starts_with(%60territori_codi%60%2C%20%2208%22))%0AGROUP%20BY%20%60id_eleccio%60%2C%20%60territori_codi%60%0AORDER%20BY%20%60id_eleccio%60%20DESC%20NULL%20LAST',
                urlInfo: 'https://analisi.transparenciacatalunya.cat/resource/irrv-2mfc.json?$query=SELECT%20%60id_eleccio%60%0AWHERE%0A%20%20caseless_contains(%60nom_eleccio%60%2C%20%22Eleccions%20Municipals%22)%0A%20%20AND%20(caseless_one_of(%60id_nivell_territorial%60%2C%20%22MU%22)%0A%20%20%20%20%20%20%20%20%20AND%20caseless_starts_with(%60territori_codi%60%2C%20%2208%22))%0AGROUP%20BY%20%60id_eleccio%60%0AORDER%20BY%20%60id_eleccio%60%20DESC%20NULL%20LAST',
                // year key from url_info is 'id_eleccio' (e.g. "M20231"); stored year is substr(id_eleccio, 1, 4)
                extra: ['import' => 'simple', 'munBy' => 'code', 'munField' => 'territori_codi', 'field1' => 'sum_votants', 'field2' => 'sum_cens_electoral', 'yearFromEleccio' => true],
            ),
            '17.1.1' => new IndicatorDefinition(
                indicatorId: '17.1.1',
                targetId: '17.1',
                targetName: "Enfortir l'autonomia i la capacitat econòmica i fiscal dels governs locals.",
                sdg: 17,
                indicatorName: 'Deute viu per habitant',
                indicatorDescription: 'El deute viu es calcula tenint en compte les operacions de risc en crèdits financers, valors de renda fixa i préstecs o crèdits transferits a tercers.',
                sign: true,
                source: 'DO',
                unit: 'eur',
                scale: 1,
                url: 'https://analisi.transparenciacatalunya.cat/resource/c9ag-cye6.json?$query=SELECT%20%60any%60%2C%20%60deute_viu%60%2C%20%60codi_ens%60%2C%20%60cens%60%0AWHERE%0A%20%20caseless_one_of(%60any%60%2C%20%22[[[year]]]%22)%0A%20%20AND%20starts_with(%60codi_ens%60%2C%20%228%22)%20AND%20(%60codi_ens%60%20%3E%20%22800180000%22)%20limit%205000',
                urlInfo: 'https://analisi.transparenciacatalunya.cat/resource/c9ag-cye6.json?$query=SELECT%20%60any%60%20GROUP%20BY%20%60any%60%20ORDER%20BY%20%60any%60%20DESC%20NULL%20LAST',
                extra: ['import' => 'simple', 'munBy' => 'codi_ens', 'munField' => 'codi_ens', 'field1' => 'deute_viu', 'field2' => 'cens'],
            ),
        ];
    }

    protected function import(IndicatorDefinition $def, Indicator $indicator, EtlContext $context): void
    {
        $years = $this->do->getYears($def->urlInfo);

        if ($this->shouldImport(ImportScope::Municipality)) {
            match ($def->extra['import']) {
                'aggregated' => $this->fetchAndStoreAggregated($def, $indicator, $years),
                'combined' => $this->fetchAndStoreCombined($def, $indicator, $years),
                default => $this->fetchAndStoreSimple($def, $indicator, $years),
            };
        }
    }

    // -------------------------------------------------------------------------
    // Simple import — most indicators
    // -------------------------------------------------------------------------

    private function fetchAndStoreSimple(IndicatorDefinition $def, Indicator $indicator, array $years): void
    {
        foreach ($years as $year) {
            $this->logger->debug(sprintf('Importing municipality data for year %s (%s)', $year, $def->indicatorId));

            $url = str_replace('[[[year]]]', $year, $def->url);
            $data = $this->http->request('GET', $url)->toArray();

            foreach (array_chunk($data, 1000) as $chunk) {
                $this->processSimpleChunk($chunk, $def, $indicator);
                gc_collect_cycles();
            }

            $this->em->flush();
            unset($data);
        }
    }

    private function processSimpleChunk(array $chunk, IndicatorDefinition $def, Indicator $indicator): void
    {
        $extra = $def->extra;

        foreach ($chunk as $row) {
            $year = isset($extra['yearFromEleccio'])
                ? (int) substr($row['id_eleccio'], 1, 4)
                : (int) $row['any'];

            $field1 = $extra['field1'];
            $field2 = $extra['field2'] ?? null;

            $value = isset($row[$field1]) ? $row[$field1] : null;
            $value2 = $field2 ? ($row[$field2] ?? null) : null;

            if (null === $value) {
                continue;
            }

            // 11.7.1: denormalize — store total m2 instead of m2/hab
            if (!empty($extra['denormalize']) && null !== $value2) {
                $value = $value * $value2;
            }

            $mun = $this->resolveMunicipality($row, $extra);
            if (!$mun) {
                continue;
            }

            $this->setMunicipalityValue($def, $indicator, $mun, $year, (float) $value, null !== $value2 ? (float) $value2 : null);
        }
    }

    private function resolveMunicipality(array $row, array $extra): ?object
    {
        $munBy = $extra['munBy'];
        $munField = $extra['munField'];

        if ('name' === $munBy) {
            return $this->geo->getMunicipalityByName($row[$munField]);
        }

        if ('codi_ens' === $munBy) {
            // 17.1.1: code format "801930008" → "080193"
            $code = '0'.substr($row[$munField], 0, 5);

            return $this->geo->getMunicipalityByCode($code);
        }

        return $this->getMunicipalityByCode($row[$munField]);
    }

    // -------------------------------------------------------------------------
    // Aggregated import — 2.1.1 (organic farmland surface)
    // -------------------------------------------------------------------------

    private function fetchAndStoreAggregated(IndicatorDefinition $def, Indicator $indicator, array $years): void
    {
        foreach ($years as $year) {
            $this->logger->debug(sprintf('Importing aggregated data for year %s (%s)', $year, $def->indicatorId));

            [$data, $aggregatedTotals] = $this->do->getHectareesByYear($year);

            foreach ($data as $row) {
                if ('S' !== $row['ccpae_e']) {
                    continue;
                }

                $munCode = $row['id_mun'];
                $munName = $row['nom_mun'];
                $rowYear = $row['campanya'];
                $value = $row['sum_sup_neta_h'];
                $value2 = $aggregatedTotals[$munCode] ?? 0;

                if (!$value2) {
                    continue;
                }

                $mun = $this->geo->getMunicipalityByName($munName);
                if (!$mun) {
                    $this->logger->error(sprintf('Municipality not found: %s (%s)', $munName, $munCode));
                    continue;
                }

                $this->setMunicipalityValue($def, $indicator, $mun, (int) $rowYear, (float) $value, (float) $value2);
            }

            $this->em->flush();
            gc_collect_cycles();
        }
    }

    // -------------------------------------------------------------------------
    // Combined import — 8.3.4 (PIME public procurement share)
    // -------------------------------------------------------------------------

    private function fetchAndStoreCombined(IndicatorDefinition $def, Indicator $indicator, array $years): void
    {
        foreach ($years as $year) {
            $this->logger->debug(sprintf('Importing combined data for year %s (%s)', $year, $def->indicatorId));

            $url = str_replace('[[[year]]]', $year, $def->url);
            $data = $this->http->request('GET', $url)->toArray();

            $pimes = [];
            $totals = [];

            foreach ($data as $row) {
                $munCode = substr($row['codi_ine10'], 0, 6);

                $pimes[$munCode] ??= 0;
                $totals[$munCode] ??= 0;

                $typesRaw = explode('||', $row['tipus_empresa']);
                $isPime = in_array('PIME', $typesRaw, true);
                $count = (int) $row['count_tipus_empresa'];

                if ($isPime) {
                    $pimes[$munCode] += $count;
                }
                $totals[$munCode] += $count;
            }

            foreach ($totals as $munCode => $total) {
                $mun = $this->geo->getMunicipalityByCode($munCode);
                if (!$mun) {
                    continue;
                }

                $this->setMunicipalityValue($def, $indicator, $mun, (int) $year, (float) ($pimes[$munCode] ?? 0), (float) $total);
            }

            $this->em->flush();
            gc_collect_cycles();
            unset($data, $pimes, $totals);
        }
    }
}
