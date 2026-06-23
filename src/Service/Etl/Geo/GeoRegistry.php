<?php

namespace App\Service\Etl\Geo;

use App\Entity\Comarca;
use App\Entity\Municipality;
use App\Entity\Province;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Resolves Province / Comarca / Municipality entities from the various code and
 * name formats the source APIs return. Caches lookups per request.
 *
 * Code normalisation in getMunicipalityByCode covers the most common quirk: Barcelona-
 * province codes ("08XXX" / "08XXXX") that some APIs return without the leading zero,
 * e.g. "80018" instead of "080018".
 */
class GeoRegistry
{
    /** @var array<string, Municipality|null> */
    private array $municipalities = [];

    /** @var array<string, Comarca|null> */
    private array $comarques = [];

    /** @var array<string, Province|null> */
    private array $provinces = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getProvince(): ?Province
    {
        return $this->entityManager->getRepository(Province::class)->findOneBy(['province_code' => '8']);
    }

    public function getProvinceByCode(string $province_code): ?Province
    {
        $province_code = preg_replace('/\D/', '', $province_code);
        if (str_starts_with($province_code, '0')) {
            $province_code = substr($province_code, 1);
        }

        if (isset($this->provinces[$province_code])) {
            return $this->provinces[$province_code];
        }

        $province = $this->entityManager->getRepository(Province::class)->findOneBy(['province_code' => $province_code]);
        if ($province) {
            $this->provinces[$province_code] = $province;
        }

        return $province;
    }

    public function getComarcaByCode(string $comarca_code): ?Comarca
    {
        $comarca_code = preg_replace('/\D/', '', $comarca_code);
        if (str_starts_with($comarca_code, '0')) {
            $comarca_code = substr($comarca_code, 1);
        }

        if (isset($this->comarques[$comarca_code])) {
            return $this->comarques[$comarca_code];
        }

        $comarca = $this->entityManager->getRepository(Comarca::class)->findOneBy(['comarca_code' => $comarca_code]);
        if ($comarca) {
            $this->comarques[$comarca_code] = $comarca;
        }

        return $comarca;
    }

    /**
     * Resolves a municipality from a raw code string, with format normalisation.
     *
     * Handled formats:
     *   "08001"  → 5-digit standard           → lookup by municipality_code
     *   "080018" → 6-digit standard           → lookup by municipality_code_6
     *   "80018"  → 5-digit, leading 0 missing → padded to "080018" → 6-digit lookup
     */
    public function getMunicipalityByCode(string $rawCode): ?Municipality
    {
        $code = preg_replace('/\D/', '', $rawCode);

        // Restore the stripped leading zero for Barcelona codes.
        // Province 08 codes stored as "8XXXX" (5 digits) need "0" prepended.
        if (5 === strlen($code) && str_starts_with($code, '8')) {
            $code = '0'.$code;
        }

        if (isset($this->municipalities[$code])) {
            return $this->municipalities[$code];
        }

        $filterField = 5 === strlen($code) ? 'municipality_code' : 'municipality_code_6';
        $municipality = $this->entityManager->getRepository(Municipality::class)->findOneBy([$filterField => $code]);

        if ($municipality) {
            $this->municipalities[$code] = $municipality;
        }

        return $municipality;
    }

    public function getMunicipalityByName(string $municipi_name): ?Municipality
    {
        $municipi_name = $this->normaliseMunicipalityName($municipi_name);

        $municipality = $this->entityManager->getRepository(Municipality::class)->findOneBy(['municipality_name' => $municipi_name]);

        if (!$municipality && isset(self::MAPPED_MUNICIPALITY_NAMES[$municipi_name])) {
            $alias = self::MAPPED_MUNICIPALITY_NAMES[$municipi_name];
            $municipality = $this->entityManager->getRepository(Municipality::class)->findOneBy(['municipality_name' => $alias]);
        }

        if (!$municipality) {
            echo "Could not find municipality $municipi_name\n";

            return null;
        }

        return $municipality;
    }

    public function getComarcaByName(string $comarca_name): ?Comarca
    {
        $comarca = $this->entityManager->getRepository(Comarca::class)->findOneBy(['comarca_name' => $comarca_name]);

        if (!$comarca && isset(self::MAPPED_COMARCA_NAMES[$comarca_name])) {
            $alias = self::MAPPED_COMARCA_NAMES[$comarca_name];
            $comarca = $this->entityManager->getRepository(Comarca::class)->findOneBy(['comarca_name' => $alias]);
        }

        if (!$comarca) {
            $this->logger->error("Could not find comarca $comarca_name");

            return null;
        }

        $this->logger->debug("Found comarca {$comarca->getComarcaName()}");

        return $comarca;
    }

    /**
     * @return array<string, Comarca> keyed by comarca_code
     */
    public function getAllComarques(): array
    {
        $formatted = [];
        foreach ($this->entityManager->getRepository(Comarca::class)->findAll() as $comarca) {
            $formatted[$comarca->getComarcaCode()] = $comarca;
        }

        return $formatted;
    }

    /**
     * @return list<string> municipality codes for the comarca, or empty list if not found
     */
    public function getMunicipalityCodesByComarcaCode(string $com_code): array
    {
        $comarca = $this->getComarcaByCode($com_code);
        if (!$comarca) {
            return [];
        }

        $codes = [];
        foreach ($comarca->getMunicipalities() as $mun) {
            $codes[] = $mun->getMunicipalityCode();
        }

        return $codes;
    }

    /**
     * Normalise municipality names from various source formats:
     *   - "Garriga, la" → "La Garriga"  (article appended with comma)
     *   - "GARRIGA (LA)" → "La Garriga" (article appended in parens, upper-case input)
     *   - Handles l'/el/la/les/els in both cases.
     */
    private function normaliseMunicipalityName(string $name): string
    {
        $articleSuffixes = [
            ", l'" => "L'",  ', el' => 'El ',  ', la' => 'La ',  ', les' => 'Les ', ', els' => 'Els ',
            ", L'" => "L'",  ', El' => 'El ',  ', La' => 'La ',  ', Les' => 'Les ', ', Els' => 'Els ',
        ];
        foreach ($articleSuffixes as $suffix => $prefix) {
            if (str_contains($name, $suffix)) {
                $name = $prefix.str_replace($suffix, '', $name);
            }
        }

        $parenSuffixes = [
            ' (LA)' => 'La ',  ' (LES)' => 'Les ', ' (ELS)' => 'Els ',
            ' (EL)' => 'El ',  " (L')" => "L'",
        ];
        foreach ($parenSuffixes as $suffix => $prefix) {
            if (str_contains($name, $suffix)) {
                $name = $prefix.str_replace($suffix, '', $name);
            }
        }

        return $name;
    }

    /**
     * Aliases for source-name → canonical DB municipality_name. Used as a fallback in
     * getMunicipalityByName when the source supplies a non-canonical spelling (truncated,
     * mis-cased, or accent-stripped).
     */
    private const MAPPED_MUNICIPALITY_NAMES = [
        'CASTELLET I LA GORNA' => 'Castellet i la Gornal',
        'BELANYA' => 'Balenyà',
        'MASNOU, EL' => 'El Masnou',
        'SANT VICENC DE MONTA' => 'Sant Vicenç de Montalt',
        'CASTELLFOLLIT DE RIU' => 'Castellfollit de Riubregós',
        'SANT SADURNI D\'OSORM' => 'Sant Sadurní d\'Osormort',
        'FOGARS DE TORDERA' => 'Fogars de la Selva',
        'BRUC, EL' => 'El Bruc',
        'ESPUNYOLA, L\'' => 'L\'Espunyola',
        'SANTA MARIA DE CORCO' => "L'Esquirol",
        'Santa Maria de Corcó' => "L'Esquirol",
        'SANT BARTOMEU DEL GR' => 'Sant Bartomeu del Grau',
        'FRANQUESES DEL VALLE' => 'Les Franqueses del Vallès',
        'PRAT DE LLOBREGAT, E' => 'El Prat de Llobregat',
        'CASTELLBELL I EL VIL' => 'Castellbell i el Vilar',
        'SANTA COLOMA DE CERV' => 'Santa Coloma de Cervelló',
        'SANTA EUGENIA DE BER' => 'Santa Eugènia de Berga',
        'CASTELLVI DE LA MARC' => 'Castellví de la Marca',
        'SANT SALVADOR DE GUARDIA' => 'Sant Salvador de Guardiola',
        'LLAGOSTA, LA' => 'La Llagosta',
        'SANT LLORENC D\'HORTO' => 'Sant Llorenç d\'Hortons',
        'MASIES DE VOLTREGA,LES' => 'Les Masies de Voltregà',
        'ESTANY, L\'' => 'L\'Estany',
        'NOU DE BERGUEDA, LA' => 'La Nou de Berguedà',
        'HOSPITALET DE LLOBREGAT' => 'L\'Hospitalet de Llobregat',
        'ROCA DEL VALLES, LA' => 'La Roca del Vallès',
        'SANT ANFREU DE LA BARCA' => 'Sant Andreu de la Barca',
        'POBLA DE CLARAMUNT,LA' => 'La Pobla de Claramunt',
        'GARRIGA, LA' => 'La Garriga',
        'SANTA EULALIA DE RON' => 'Santa Eulàlia de Ronçana',
        'SANTA MARIA DE PALAU' => 'Santa Maria de Palautordera',
        'SANTA PERPETUA DE MO' => 'Santa Perpètua de Mogoda',
        'MONISTROL DE MONTSER' => 'Monistrol de Montserrat',
        'GRANADA, LA' => 'La Granada',
        'SANT JOAN DE VILATOR' => 'Sant Joan de Vilatorrada',
        'SAN FELIU DE CODINES' => 'Sant Feliu de Codines',
        'HOSTALETS DE PIEROLA' => 'Els Hostalets de Pierola',
        'POBLA DE LILLET, LA' => 'La Pobla de Lillet',
        'AMETLLA DEL VALLES,' => 'L\'Ametlla del Vallès',
        'PALAU DE PLEGAMANS' => 'Palau-solità i Plegamans',
        'BIGUES I RIELLS' => 'Bigues i Riells del Fai',
        'PALMA DE CERVELLO, LA' => 'La Palma de Cervelló',
        'SANT CEBRIA DE VALLA' => 'Sant Cebrià de Vallalta',
        'SANTA MARIA DE MERLE' => 'Santa Maria de Merlès',
        'MASIES DE RODA, LES' => 'Les Masies de Roda',
        'SANTA MARIA DE MIRAL' => 'Santa Maria de Miralles',
        'SANT MARTI DE CENTEL' => 'Sant Martí de Centelles',
        'SANT AGUSTI DE LLUCA' => 'Sant Agustí de Lluçanès',
        'SANTA CECILIA DE VOL' => 'Santa Cecília de Voltregà',
        'SANT FOST DE CAMPSEN' => 'Sant Fost de Campsentelles',
        'CASTELLFOLLIT DEL BO' => 'Castellfollit del Boix',
        'TORRE DE CLARAMUNT,LA' => 'La Torre de Claramunt',
        'SANT MARTI DE TOUS' => 'Sant Martí de Tous',
        'SANT MARTI SARROCA' => 'Sant Martí Sarroca',
        'SANT MARTI SESGUEIOLES' => 'Sant Martí Sesgueioles',
        'SANT MATEU DE BAGES' => 'Sant Mateu de Bages',
        'SANT PERE DE RIBES' => 'Sant Pere de Ribes',
        'SANT PERE DE RIUDEBITLLES' => 'Sant Pere de Riudebitlles',
        'SANT PERE DE TORELLO' => 'Sant Pere de Torelló',
        'SANT PERE DE VILAMAJOR' => 'Sant Pere de Vilamajor',
        'SANT PERE SALLAVINERA' => 'Sant Pere Sallavinera',
        'SANT POL DE MAR' => 'Sant Pol de Mar',
        'SANT QUINTI DE MEDIONA' => 'Sant Quintí de Mediona',
        'SANT QUIRZE DE BESORA' => 'Sant Quirze de Besora',
        'SANT QUIRZE DEL VALLES' => 'Sant Quirze del Vallès',
        'SANT QUIRZE SAFAJA' => 'Sant Quirze Safaja',
        'SANT SADURNI D\'ANOIA' => 'Sant Sadurní d\'Anoia',
        'SANT SADURNI D\'OSORMORT' => 'Sant Sadurní d\'Osormort',
        'SANT VICENC DE CASTELLET' => 'Sant Vicenç de Castellet',
        'SANT VICENC DE MONTALT' => 'Sant Vicenç de Montalt',
        'SANT VICENC DE TORELLO' => 'Sant Vicenç de Torelló',
        'SANT VICENC DELS HORTS' => 'Sant Vicenç dels Horts',
        'SANTA COLOMA DE GRAMANET' => 'Santa Coloma de Gramenet',
        'SANTA EUGENIA DE BERGA' => 'Santa Eugènia de Berga',
        'SANTA EULALIA DE RIUPRIMER' => 'Santa Eulàlia de Riuprimer',
        'SANTA EULALIA DE RONÇANA' => 'Santa Eulàlia de Ronçana',
        'SANTA FE DEL PENEDES' => 'Santa Fe del Penedès',
        'SANTA MARGARIDA DE MONTBUI' => 'Santa Margarida de Montbui',
        'SANTA MARGARIDA I ELS MONJOS' => 'Santa Margarida i els Monjos',
        'SANTA MARIA D\'OLO' => 'Santa Maria d\'Oló',
        'SANTA MARIA DE BESORA' => 'Santa Maria de Besora',
        'SANTA MARIA DE MARTORELLES' => 'Santa Maria de Martorelles',
        'SANTA MARIA DE MERLES' => 'Santa Maria de Merlès',
        'SANTA MARIA DE MIRALLES' => 'Santa Maria de Miralles',
        'SANTA MARIA DE PALAUTORDERA' => 'Santa Maria de Palautordera',
        'SANTA PERPETUA DE MOGODA' => 'Santa Perpètua de Mogoda',
        'SANTA SUSANNA' => 'Santa Susanna',
        'SANTPEDOR' => 'Santpedor',
        'SENTMENAT' => 'Sentmenat',
        'SEVA' => 'Seva',
        'SITGES' => 'Sitges',
        'SOBREMUNT' => 'Sobremunt',
        'SORA' => 'Sora',
        'SUBIRATS' => 'Subirats',
        'SURIA' => 'Súria',
        'TAGAMANENT' => 'Tagamanent',
        'TALAMANCA' => 'Talamanca',
        'TARADELL' => 'Taradell',
        'TAVÈRNOLES' => 'Tavèrnoles',
        'TAVERTET' => 'Tavertet',
        'TEIA' => 'Teià',
        'TERRASSA' => 'Terrassa',
        'TIANA' => 'Tiana',
        'TONA' => 'Tona',
        'TORDERA' => 'Tordera',
        'TORELLO' => 'Torelló',
        'TORRELAVIT' => 'Torrelavit',
        'TORRELLES DE FOIX' => 'Torrelles de Foix',
        'TORRELLES DE LLOBREGAT' => 'Torrelles de Llobregat',
        'ULLASTRELL' => 'Ullastrell',
        'VACARISSES' => 'Vacarisses',
        'VALLBONA D\'ANOIA' => 'Vallbona d\'Anoia',
        'VALLCEBRE' => 'Vallcebre',
        'VALLGORGUINA' => 'Vallgorguina',
        'VALLIRANA' => 'Vallirana',
        'VALLROMANES' => 'Vallromanes',
        'VECIANA' => 'Veciana',
        'VIC' => 'Vic',
        'VILADA' => 'Vilada',
        'VILADECANS' => 'Viladecans',
        'VILADECAVALLS' => 'Viladecavalls',
        'VILAFRANCA DEL PENEDES' => 'Vilafranca del Penedès',
        'VILALBA SASSERRA' => 'Vilalba Sasserra',
        'VILANOVA DE SAU' => 'Vilanova de Sau',
        'VILANOVA DEL CAMI' => 'Vilanova del Camí',
        'VILANOVA DEL VALLES' => 'Vilanova del Vallès',
        'VILANOVA I LA GELTRU' => 'Vilanova i la Geltrú',
        'VILASSAR DE DALT' => 'Vilassar de Dalt',
        'VILASSAR DE MAR' => 'Vilassar de Mar',
        'VILOBI DEL PENEDES' => 'Vilobí del Penedès',
        'VIVER I SERRATEIX' => 'Viver i Serrateix',
        'BRULL, EL' => 'El Brull',
        'PAPIOL, EL' => 'El Papiol',
        'SANTA MARGARIDA DE M' => 'Santa Margarida de Montbui',
        'PONT DE VILOMARA I R' => 'EL Pont de Vilomara i Rocafort',
        'SANT VICENC DE CASTELLERS' => 'Sant Vicenç de Castellet',
        'PLA DEL PENEDES, EL' => 'El Pla del Penedès',
        'CABANYES, LES' => 'Les Cabanyes',
        'SANT JULIA DE VILATO' => 'Sant Julià de Vilatorta',
        'CABRERA D\'IGUALADA' => "Cabrera d'Anoia",
        'SANT ISCLE DE VALLAL' => 'Sant Iscle de Vallalta',
        'SANT PERE SALLAVINER' => 'Sant Pere Sallavinera',
        'LLACUNA, LA' => 'La Llacuna',
        'PRATS DE REI, ELS' => 'Els Prats de Rei',
        'SANT ESTEVE DE PALAU' => 'Sant Esteve de Palautordera',
        'SANT ANTONI DE VILAM' => 'Sant Antoni de Vilamajor',
        'QUAR, LA' => 'La Quar',
        'MONTMANY-FIGARO' => 'Figaró-Montmany',
        'SANTA EULALIA DE RIU' => 'Santa Eulàlia de Riuprimer',
        'SANT QUINTI DE MEDIO' => 'Sant Quintí de Mediona',
        'El Hostalets de Pierolas' => 'Els Hostalets de Pierola',
        'El Prats de Reis' => 'Els Prats de Rei',
        'Bigues i Riells' => 'Bigues i Riells del Fai',
    ];

    private const MAPPED_COMARCA_NAMES = [
        'Consell Comarcal d´Osona' => 'Osona',
        'Àrea Metropolitana de Barcelona' => 'Barcelonès',
        'Consell Comarcal del Vallès Occidental' => 'Vallès Occidental',
        'Consell Comarcal de l´Alt Penedès' => 'Alt Penedès',
        'Consell Comarcal del Vallès Oriental' => 'Vallès Oriental',
        'Consell Comarcal de l´Anoia' => 'Anoia',
        'Consell Comarcal del Berguedà' => 'Berguedà',
        'Consell Comarcal del Maresme' => 'Maresme',
        'Consell Comarcal del Garraf' => 'Garraf',
    ];
}
