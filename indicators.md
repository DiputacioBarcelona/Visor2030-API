# Indicadors

Llistat d'indicadors que es poden consultar a través de l'API del Visor2030 i els significats de les dades que es poden obtenir.

- [1](#1)
  - [1.2](#12)
    - [1.2.1 % Població amb ingressos < 60% [FET]](#121-població-amb-ingressos-60-fet)
    - [1.2.2 Renda mediana [FET]](#122-renda-mediana-fet)
  - [1.3](#13)
    - [1.3.1 Renda Gar. Ciut. per 10.000 hab. [FET]](#131-renda-gar-ciut-per-10000-hab-fet)
- [2](#2)
  - [2.1](#21)
    - [2.1.1 % Superfície agrícola ecològica [FET]](#211-superfície-agrícola-ecològica-fet)
  - [2.3](#23)
    - [2.3.1 Terres agrícoles per 100 hab [FET]](#231-terres-agrícoles-per-100-hab-fet)
- [3](#3)
  - [3.4](#34)
    - [3.4.1 Índex de Sobreenvelliment [FET]](#341-índex-de-sobreenvelliment-fet)
  - [3.6](#36)
    - [3.6.1 Taxa mortalitat trànsit [FET]](#361-taxa-mortalitat-trànsit-fet)
- [4](#4)
  - [4.1](#41)
    - [4.1.1 Taxa matriculació PFI [REFER]](#411-taxa-matriculació-pfi-refer)
  - [4.2](#42)
    - [4.2.1 Taxa matriculació educació infantil [REFER]](#421-taxa-matriculació-educació-infantil-refer)
- [5](#5)
  - [5.5](#55)
    - [5.5.1 % Càrrecs electes dones](#551-càrrecs-electes-dones)
  - [5.c](#5c)
    - [5.c.1 Taxa atur dones [PENDENT]](#5c1-taxa-atur-dones-pendent)
    - [5.c.2 Taxa ocupació dones [PENDENT]](#5c2-taxa-ocupació-dones-pendent)
- [8](#8)
  - [8.2](#82)
    - [8.2.1 PIB per habitant [REFER]](#821-pib-per-habitant-refer)
  - [8.3](#83)
    - [8.3.1 Taxa d'ocupació [FET]](#831-taxa-docupació-fet)
    - [8.3.2 Est. comercials per 1.000 hab. [FET]](#832-est-comercials-per-1000-hab-fet)
  - [8.9](#89)
    - [8.9.1 Pressió turística [FET]](#891-pressió-turística-fet)
- [9](#9)
  - [9.5](#95)
    - [9.5.2 Llocs de treball indústria [FET]](#952-llocs-de-treball-indústria-fet)
- [10](#10)
  - [10.1](#101)
    - [10.1.1 % Població amb ingressos < 40% [FET]](#1011-població-amb-ingressos-40-fet)
    - [10.1.2 Índex Gini [FET]](#1012-índex-gini-fet)
    - [10.1.3 Desigualtat per Renda [FET]](#1013-desigualtat-per-renda-fet)
    - [10.1.4 Renda per habitant [FET]](#1014-renda-per-habitant-fet)
- [11](#11)
  - [11.1](#111)
    - [11.1.1 Esforç econòmic lloguer [REFER]](#1111-esforç-econòmic-lloguer-refer)
  - [11.3](#113)
    - [11.3.1 Percentatge de sòl artificialitztat [FET]](#1131-percentatge-de-sòl-artificialitztat-fet)
  - [11.4](#114)
    - [11.4.1 Superfície (m2) zona verda [FET]](#1141-superfície-m2-zona-verda-fet)
  - [11.7](#117)
    - [11.7.1 Superfície (m2) zona verda [FET]](#1171-superfície-m2-zona-verda-fet)
- [12](#12-1)
  - [12.5](#125)
    - [12.5.1 Taxa recollida selectiva [FET]](#1251-taxa-recollida-selectiva-fet)
    - [12.5.2 Residus domèstics en kg/hab/any [FET]](#1252-residus-domèstics-en-kghabany-fet)
  - [12.8](#128)
    - [12.8.1 Nombre de socis de cooperatives per habitant](#1281-nombre-de-socis-de-cooperatives-per-habitant)
- [15](#15)
  - [15.1](#151)
    - [15.1.1 % superfície protecció local](#1511-superfície-protecció-local)
    - [15.1.2 % superfície protecció reglada](#1512-superfície-protecció-reglada)
- [16](#16)
  - [16.7](#167)
    - [16.7.1 Participació eleccions municipals [FET]](#1671-participació-eleccions-municipals-fet)
- [17](#17)
  - [17.4](#174)
    - [17.4.1 Deute viu per habitant [FET]](#1741-deute-viu-per-habitant-fet)
- [Annex](#annex)
  - [Població total](#població-total)
  - [Població de 16 a 64 anys](#població-de-16-a-64-anys)
    - [Idescat](#idescat)
    - [Idescat 2 No té la dada de 2023. S'han de sumar els valors per a cada edat.](#idescat-2-no-té-la-dada-de-2023-shan-de-sumar-els-valors-per-a-cada-edat)
    - [INE No té dada de 2023. S'han de sumar els valors per a cada edat.](#ine-no-té-dada-de-2023-shan-de-sumar-els-valors-per-a-cada-edat)

## 1

### 1.2

Reduir la proporció de població que viu en la pobresa, augmentant els programes integrals que l'abordin en totes les seves dimensions.

#### 1.2.1 % Població amb ingressos < 60% [FET]

Percentatge de població que viu en unitats de consum amb una renda disponible inferior al 60% de la mitjana.

- FONT: INE
- URL:

```
https://servicios.ine.es/wstempus/js/ES/datos_tabla/30901?tv=19:&tv=848:322972&tv=18:451
```

- **Notes**: l'API ens dóna directament el percentatge de la població amb ingressos < 60 %. Primer haurem de normalitzar les dades (multiplicar per la població total).
- Com es desnormalitza això?? ha de ser respecte la població total? o només la poblacio que treballa??...
- **value**: població amb ingressos < 60 %.
- **value2**: [POBLACIO_TOTAL](#població-total)
- **calculation**: value \* 100 / value2

#### 1.2.2 Renda mediana [FET]

Valor de renda que, ordenant a tots els individus de menor a major ingrés, deixa una meitat dels mateixos per sota d'aquest valor i l'altra meitat per sobre.

- FONT: INE
- URL:

```
https://servicios.ine.es/wstempus/js/ES/datos_tabla/30896?tv=19:&tv=482:382441
```

- NOTES: Això es pot desnormalitzar...?? si es una renda mediana...? jo no ho veig
- **value**: renda mediana per unitat de consum.
- **value2**: `null`
- **calculation**: value

### 1.3

Reforçar en l'àmbit local sistemes i mesures apropiades de protecció social per a totes les persones, aconseguint una àmplia cobertura de les persones vulnerables.

#### 1.3.1 Renda Gar. Ciut. per 10.000 hab. [FET]

- URL principal (per saber els anys disponibles): https://www.idescat.cat/pub/?id=ris&n=9549&geo=cat&f=ssv
- URL província de Barcelona: https://www.idescat.cat/pub/?id=ris&n=9549&geo=prov%3A08&f=ssv
- URL comarques: https://www.idescat.cat/pub/?id=ris&n=9549&geo=com&t=[[[year]]]00&f=ssv
- URL municipis: https://www.idescat.cat/pub/?id=ris&n=9549&geo=mun&t=[[[year]]]00&f=ssv
- URL secundària: POBLACIÓ_TOTAL

- Notes: Per filtrar per any s'ha de canviar el `[[[year]]]`.
- **value**: Mitjana de persones beneficiàries
- **value2**: POBLACIO_TOTAL
- **calculation**: `value` \* 10000 / `value2`

### 1.4

Garantir que les persones, especialment les pobres i vulnerables, tinguin els mateixos drets, així com accés als serveis bàsics, recursos naturals, econòmics i financers en igualtat de condicions.

#### 1.4.1 Taxa de prestacions desocupació

https://www.diba.cat/hg2/presentacioMun.asp?prId=1327&idioma=cat&codi_any=2024&codi_mes=9&format=pantalla

en json:

https://www.diba.cat/hg2/presentacioMun.asp?prId=1327&idioma=cat&codi_any=2024&codi_mes=9&format=json

## 2

### 2.1

Fomentar la productivitat agrícola i els ingressos de les persones que es dediquen a la producció d’aliments a petita escala.

#### 2.1.1 % Superfície agrícola ecològica [FET]

- FONT: DO_DO
- URL principal: https://analisi.transparenciacatalunya.cat/Medi-Rural-Pesca/Dades-de-parcel-les-d-explotacions-DUN-de-Cataluny/si4p-ygat/about_data

API endpoint:

```
https://analisi.transparenciacatalunya.cat/resource/si4p-ygat.json?$query=SELECT%0A%20%20%60id_mun%60%2C%0A%20%20sum(%60sup_neta_h%60)%20AS%20%60sum_sup_neta_h%60%2C%0A%20%20%60ccpae_e%60%2C%0A%20%20%60campanya%60%0AWHERE%0A%20%20(%60campanya%60%20IN%20(%222023%22))%0A%20%20AND%20caseless_one_of(%60pro%60%2C%20%2208%22)%0AGROUP%20BY%20%60id_mun%60%2C%20%60ccpae_e%60%2C%20%60campanya%60%0AORDER%20BY%0A%20%20%60id_mun%60%20ASC%20NULL%20FIRST%2C%0A%20%20%60ccpae_e%60%20ASC%20NULL%20FIRST%2C%0A%20%20%60campanya%60%20ASC%20NULL%20FIRST%0ALIMIT%2010000%0AOFFSET%200&
```

ES PODEN AGREGAR COLUMNES I AGRUPAR!!

```
https://analisi.transparenciacatalunya.cat/resource/si4p-ygat.json?$query=SELECT
  `id_mun`,
  sum(`sup_neta_h`) AS `sum_sup_neta_h`,
  `ccpae_e`,
  `campanya`
WHERE caseless_one_of(`pro`, "08") AND (`campanya` IN ("2023"))
GROUP BY `id_mun`, `ccpae_e`, `campanya`
ORDER BY
  `id_mun` ASC NULL FIRST,
  `ccpae_e` ASC NULL FIRST,
  `campanya` ASC NULL FIRST
LIMIT 10000
OFFSET 0&
```

Aquest endpoint està filtrat per any, cal canviar l'any per cada cas. (fixeu-vos en el placeholder `[[[year]]]`)

```
https://analisi.transparenciacatalunya.cat/resource/si4p-ygat.json?$query=SELECT%0A%20%20%60id_mun%60%2C%0A%20%20sum(%60sup_neta_h%60)%20AS%20%60sum_sup_neta_h%60%2C%0A%20%20%60ccpae_e%60%2C%0A%20%20%60campanya%60%0AWHERE%0A%20%20(%60campanya%60%20IN%20(%22[[[year]]]%22))%0A%20%20AND%20caseless_one_of(%60pro%60%2C%20%2208%22)%0AGROUP%20BY%20%60id_mun%60%2C%20%60ccpae_e%60%2C%20%60campanya%60%0AORDER%20BY%0A%20%20%60id_mun%60%20ASC%20NULL%20FIRST%2C%0A%20%20%60ccpae_e%60%20ASC%20NULL%20FIRST%2C%0A%20%20%60campanya%60%20ASC%20NULL%20FIRST%0ALIMIT%2010000%0AOFFSET%200&
```

Primer cal obtenir els anys perquè sino hi ha massa dades:

```
https://analisi.transparenciacatalunya.cat/resource/si4p-ygat.json?$query=SELECT%20%60campanya%60%20GROUP%20BY%20%60campanya%60%20ORDER%20BY%20%60campanya%60%20DESC%20NULL%20LAST
```

- Notes: Hi ha massa dades. Importem per anys. Es pot filtrar per `campanya` i ajustar el `limit`. La columna amb les hectàrees és `sup_neta_h`.
- **value**: Hectàrees amb certificat ecològic
- **value2**: Hectàrees destinades al conreu
- **calculation**: value \* 100 / value2

### 2.3

Fomentar la productivitat agrícola i els ingressos de les persones que es dediquen a la producció d’aliments a petita escala. (igual que 2.1??)

#### 2.3.1 Terres agrícoles per 100 hab [FET]

Superfície total destinada a conreu(aliments i farratges) respecte del nombre d'habitants (hectàrees/100 habitants).

- FONT: DO
- URL principal:

```
https://analisi.transparenciacatalunya.cat/resource/yh94-j2n9.json?provincia=Barcelona
```

Millor: (filtrat per any)

```
SELECT `campanya`, `id_mun`, sum(`ha`) AS `sum_ha`
WHERE caseless_one_of(`provincia`, "Barcelona")
GROUP BY `campanya`, `id_mun`
HAVING `campanya` IN ("2016")
```

```
https://analisi.transparenciacatalunya.cat/resource/yh94-j2n9.json?$query=SELECT%20%60campanya%60%2C%20%60id_mun%60%2C%20sum(%60ha%60)%20AS%20%60sum_ha%60%0AWHERE%20caseless_one_of(%60provincia%60%2C%20%22Barcelona%22)%0AGROUP%20BY%20%60campanya%60%2C%20%60id_mun%60%0AHAVING%20%60campanya%60%20IN%20(%222016%22)
```

- Notes: Hi ha massa dades (casi un milió). La columna amb les hectàrees és `ha`.
- **value**: Hectàrees destinades al conreu
- **value2**: [POBLACIO_TOTAL](#població-total)
- **calculation**: value \* 100 / POBLACIO_TOTAL

## 3

### 3.4

Incrementar programes de prevenció i tractament de malalties no transmissibles adreçades a la reducció de la mortalitat prematura i promoure la salut mental i el benestar.

#### 3.4.1 Índex de Sobreenvelliment [FET]

Relació entre la població de 85 anys i més amb la població de 65 i més.

- FONT: IDESCAT
- URL:

```
https://api.idescat.cat/taules/v2/censph/539/5976/mun/data?sex=TOTAL&age=Y065_069,Y070_074,Y075_079,Y080_084,Y085_089,Y090_094,Y095_099,Y_GE100&mun=080018,080023,080039,080044,080057,080060,080076,080082,080095,080109,080116,080121,080137,080142,080155,080168,080174,080180,080193,080207,080214,080229,080235,080240,080253,080266,080272,080288,080291,080305,080312,080327,080333,080348,080351,080364,080370,080386,080399,080403,080410,080425,080431,080446,080459,080462,080478,080484,080497,080500,080517,080522,080538,080543,080556,080569,080575,080581,080594,080608,080615,080620,080636,080641,080654,080667,080673,080689,080692,080706,080713,080728,080734,080749,080752,080765,080771,080787,080790,080804,080811,080826,080832,080847,080850,080863,080879,080885,080898,080902,080919,080924,080930,080945,080958,080961,080977,080983,080996,081000,081017,081022,081038,081043,081056,081069,081075,081081,081094,081108,081115,081120,081136,081141,081154,081167,081173,081189,081192,081206,081213,081228,081234,081249,081252,081265,081271,081287,081290,081304,081311,081326,081332,081347,081350,081363,081379,081385,081398,081402,081419,081424,081430,081445,081458,081461,081477,081483,081496,081509,081516,081521,081537,081542,081555,081568,081574,081580,081593,081607,081614,081629,081635,081640,081653,081666,081672,081688,081691,081705,081712,081727,081748,081751,081764,081770,081786,081799,081803,081810,081825,081831,081846,081859,081878,081884,081897,081901,081918,081923,081939,081944,081957,081960,081976,081982,081995,082009,082016,082021,082037,082042,082055,082068,082074,082080,082093,082107,082114,082129,082135,082140,082153,082166,082172,082188,082191,082205,082212,082227,082233,082248,082251,082264,082270,082286,082299,082303,082310,082325,082331,082346,082359,082362,082378,082384,082397,082401,082418,082423,082439,082444,082457,082460,082476,082482,082495,082508,082515,082520,082536,082541,082554,082567,082573,082589,082592,082606,082613,082628,082634,082649,082652,082665,082671,082687,082690,082704,082711,082726,082732,082747,082750,082763,082779,082785,082798,082802,082819,082824,082830,082845,082858,082861,082877,082883,082896,082900,082917,082922,082938,082943,082956,082969,082975,082981,082994,083008,083015,083020,083036,083041,083054,083067,083073,083089,089019,089024,089030,089045,089058
```

- Notes: Hi ha massa dades i per això posem tants filtres a la URL.
- **value**: Número de persones de 85 anys i més
- **value2**: Número de persones de 65 anys i més
- **calculation**: value / value2

### 3.6

Reduir a la meitat el nombre de morts i lesions causats per accidents de trànsit.

#### 3.6.1 Taxa mortalitat trànsit [FET]

Font: DO_DO

Taxa de mortalitat per lesions degudes a accidents de trànsit

Font de dades: https://analisi.transparenciacatalunya.cat/Transport/Accidents-de-tr-nsit-amb-morts-o-ferits-greus-a-Ca/rmgc-ncpb/about_data

API endpoint principal:

```
https://analisi.transparenciacatalunya.cat/resource/rmgc-ncpb.json?$query=SELECT%0A%20%20%60any%60%2C%0A%20%20%60nommun%60%2C%0A%20%20sum(%60f_morts%60)%20AS%20%60sum_f_morts%60%2C%0A%20%20sum(%60f_victimes%60)%20AS%20%60sum_f_victimes%60%2C%0A%20%20sum(%60f_ferits_greus%60)%20AS%20%60sum_f_ferits_greus%60%2C%0A%20%20sum(%60f_ferits_lleus%60)%20AS%20%60sum_f_ferits_lleus%60%0AWHERE%20caseless_one_of(%60nomdem%60%2C%20%22Barcelona%22)%0AGROUP%20BY%20%60any%60%2C%20%60nommun%60%0AHAVING%20%60any%60%20IN%20(%222021%22)
```

API endpoint secondari (poblacions):

```
https://analisi.transparenciacatalunya.cat/resource/x5sz-niat.json?$query=SELECT%20%60codi_10%60%2C%20%60nom_ens%60%2C%20%60any%60%2C%20%60total%60%2C%20%60homes%60%2C%20%60dones%60%0AWHERE%20caseless_starts_with(%60codi_10%60%2C%20%2208%22)%20AND%20(%60any%60%20IN%20(%22[[[year]]]%22))%0AORDER%20BY%20%60any%60%20DESC%20NULL%20LAST
```

Query: (l'any del filtre es substitueix dinàmicament)

```
SELECT
  `any`,
  `nommun`,
  sum(`f_morts`) AS `sum_f_morts`,
  sum(`f_victimes`) AS `sum_f_victimes`,
  sum(`f_ferits_greus`) AS `sum_f_ferits_greus`,
  sum(`f_ferits_lleus`) AS `sum_f_ferits_lleus`
WHERE caseless_one_of(`nomdem`, "Barcelona")
GROUP BY `any`, `nommun`
HAVING `any` IN ("2021")
```

## 4

### 4.1

Reduir substancialment l'abandonament escolar prematur, assegurant l'accés a l'educació gratuïta, equitativa i de qualitat.

#### 4.1.1 Taxa matriculació PFI [REFER]

Alumnes matriculats a PFI sobre el total de població d'entre 16 i 21 anys al municipi.

- FONT: DO_IDESCAT
- URL principal:

```
https://analisi.transparenciacatalunya.cat/resource/xvme-26kg.json?codi_estudis=PFI&$limit=1000&any=
```

Millor:

```
SELECT
  `any`,
  `codi_municipi_5`,
  sum(`matr_cules_total`) AS `sum_matricules_total`
WHERE caseless_one_of(`codi_estudis`, "PFI")
GROUP BY `any`, `codi_municipi_5`
HAVING caseless_starts_with(`codi_municipi_5`, "08")
ORDER BY `any` ASC NULL FIRST, `codi_municipi_5` ASC NULL FIRST
LIMIT 100
OFFSET 0
```

```
https://analisi.transparenciacatalunya.cat/resource/xvme-26kg.json?$query=SELECT%0A%20%20%60any%60%2C%0A%20%20%60codi_municipi_5%60%2C%0A%20%20sum(%60matr_cules_total%60)%20AS%20%60sum_matricules_total%60%0AWHERE%20caseless_one_of(%60codi_estudis%60%2C%20%22PFI%22)%0AGROUP%20BY%20%60any%60%2C%20%60codi_municipi_5%60%0AHAVING%20caseless_starts_with(%60codi_municipi_5%60%2C%20%2208%22)%20limit%204000
```

- URL secundaria:

```
https://api.idescat.cat/taules/v2/censph/10/5975/mun/data?AGE=Y016,Y017,Y018,Y019,Y020,Y021&SEX=total&YEAR=
```

A ambdues URLs cal afegir l'any en el que es vol consultar les dades.

- Notes: La primera API dona el nombre d'alumnes matriculats a PFI i la segona API dona el total de població d'entre 16 i 21 anys. A la segona s'ha de filtrar també per any.
- **value**: nombre total d'alumnes matriculats a PFI
- **value2**: nombre total de població d'entre 16 i 21 anys
- **calculation**: value \* 100 / value2

### 4.2

#### 4.2.1 Taxa matriculació educació infantil [REFER]

Taxa educacio infantil 1r cicle

- FONT: DO_IDESCAT
- URL principal:

```
https://analisi.transparenciacatalunya.cat/resource/xvme-26kg.json?codi_estudis=EINF&nivell=1&$limit=10000&any=
```

Millor:

```
SELECT
  `any`,
  `codi_municipi_5`,
  sum(`matr_cules_total`) AS `sum_matricules_total`
WHERE caseless_one_of(`codi_estudis`, "EINF")
GROUP BY `any`, `codi_municipi_5`
HAVING caseless_starts_with(`codi_municipi_5`, "08")
ORDER BY `any` ASC NULL FIRST, `codi_municipi_5` ASC NULL FIRST
LIMIT 4000
OFFSET 0
```

```
https://analisi.transparenciacatalunya.cat/resource/xvme-26kg.json?$query=SELECT%0A%20%20%60any%60%2C%0A%20%20%60codi_municipi_5%60%2C%0A%20%20sum(%60matr_cules_total%60)%20AS%20%60sum_matricules_total%60%0AWHERE%20caseless_one_of(%60codi_estudis%60%2C%20%22EINF%22)%0AGROUP%20BY%20%60any%60%2C%20%60codi_municipi_5%60%0AHAVING%20caseless_starts_with(%60codi_municipi_5%60%2C%20%2208%22)%20limit%204000
```

- URL secundaria:

```
https://api.idescat.cat/taules/v2/censph/10/5975/mun/data?AGE=Y000,Y001,Y002&SEX=total&YEAR=
```

A ambdues URLs cal afegir l'any en el que es vol consultar les dades.

- Notes: La primera API dona el nombre d'alumnes de primer cicle i la segona API dona el total de població d'entre 0 i 2 anys. A la segona s'ha de filtrar també per any.
- **value**: nombre total d'alumnes matriculats a primer cicle d'educació infantil
- **value2**: nombre total de població d'entre 0 i 2 anys
- **calculation**: value \* 100 / value2

## 5

### 5.5

Vetllar per la participació plena i efectiva de les dones, i per la igualtat d’oportunitats de lideratge en tots els àmbits de presa de decisions en la vida política, econòmica i pública.

#### 5.5.1 % Càrrecs electes dones

Percentatge de càrrecs electes del consistori municipal ocupats per dones.

- FONT: Diba
- URL:

```
https://do.diba.cat/api/dataset/electes/pag-fi/4000
```

- Notes: s'han de comptar les dones a cada municipi. El total de càrrecs el tenim a `entitats`. Hi ha persones que no tenen informació a `sexe`.
- **value**: nombre de dones a càrrecs públics.
- **value2**: nombre total de càrrecs.
- **calculation**: value \* 100 / value2

### 5.c

Enfortir les polítiques i els plans de igualtat de gènere i d'empoderament de dones i nenes.

#### 5.c.1 Taxa atur dones [PENDENT]

- FONT: IDESCAT
- URL:

```
https://api.idescat.cat/taules/v2/censph/19/20185/mun?SEX=F&R_ECON_ACT=PO_UNE
```

- URL secundària:

```
https://api.idescat.cat/taules/v2/censph/19/20185/mun?SEX=F&R_ECON_ACT=PO_ACTT
```

- **value**: població (DONES) activa de 16 anys o més que està aturada (no disponible només de 16 fins a 64)
- **value2**: població (DONES) total activa
- **calculation**: value \* 100 / value2

#### 5.c.2 Taxa ocupació dones [PENDENT]

- FONT: IDESCAT
- URL:

```
https://api.idescat.cat/taules/v2/censph/16720/20208/mun/data?concept=PP_EPY1664&sex=total
```

- URL secundària:

```
https://api.idescat.cat/taules/v2/censph/540/19948/mun/data?SEX=F&AGE=Y016_064
```

- Notes: la dada de la URL principal ja és un %. Hem de desnormalitzar (multiplicar pel valor de la URL secundària i dividir entre 100) abans de guardar el resultat a `value`.
- **value**: població (DONES) ocupada de 16 a 64 anys
- **value2**: població (DONES) total
- **calculation**: value \* 100 / value2

## 8

### 8.2

Augmentar de forma generalitzada la productivitat econòmica del conjunt del territori mitjançant la diversificació, la innovació, la planificació estratègica i la concertació territorial.

#### 8.2.1 PIB per habitant [REFER]

[REFER] Cal normalitzar per habitant. S'ha d treure el numero d'habitants dun altre lloc

El producte interior brut a preus de mercat (PIB pm) mesura el resultat final de l'activitat econòmica de les unitats productores en el territori.

- FONT: IDESCAT
- URL:

```
https://api.idescat.cat/taules/v2/pibc/13830/14779/mun/data?concept=GDP_TH_E_INH
```

- Notes: La dada es és en milers d'euros. Cal multiplicar-la per 1000 per obtenir el valor real.
- **value**: PIB per habitant (milers d'€)
- **value2**: `null`
- **calculation**: value \* 1000 (scale)

### 8.3

Promoure la creació d'ocupació digna a través de la innovació, creativitat i emprenedoria, fomentant el creixement de les petites i mitjanes empreses.

#### 8.3.1 Taxa d'ocupació [FET]

(potser el mes canvia, he posat desembre de moment)

- URL principal (per saber els anys disponibles): https://www.idescat.cat/pub/?id=afi&n=8604&geo=cat&f=ssv
- URL província de Barcelona: https://www.idescat.cat/pub/?id=afi&n=8604&geo=prov%3A08&t=[[[year]]]12&f=ssv
- URL comarques: https://www.idescat.cat/pub/?id=afi&n=8604&geo=com&t=[[[year]]]12&f=ssv
- URL municipis: https://www.idescat.cat/pub/?id=afi&n=8604&geo=mun&t=[[[year]]]12&f=ssv
- URL secundària: [POBLACIÓ_16_64](#Població-de-16-a-64-anys)

- Notes: Per filtrar per any s'ha de canviar el `[[[year]]]`. Les dades són mensuals.
- **value**: Nombre d'afiliats
- **value2**: Nombre d'habitants amb edats de 16 a 64 anys
- **calculation**: `value` \* 100 / `value2`

#### 8.3.2 Est. comercials per 1.000 hab. [FET]

Nombre d'establiments comercials per cada 1.000 habitants.

- URL: https://analisi.transparenciacatalunya.cat/Comer-/Servei-de-consulta-del-Cens-d-establiments-comerci/2dhj-q3r8/about_data

API endpoint:

```
https://analisi.transparenciacatalunya.cat/resource/2dhj-q3r8.json?$query=SELECT%0A%20%20%60municipi%60%2C%0A%20%20%60any%60%2C%0A%20%20%60poblaci%60%2C%0A%20%20%60establiments%60%2C%0A%20%20%60densitat_comercial_est_1%60%0AWHERE%0A%20%20(%60any%60%20IN%20(%222017%22))%0A%20%20AND%20caseless_one_of(%0A%20%20%20%20%60mbit_territorial%60%2C%0A%20%20%20%20%22Metropolit%C3%A0%22%2C%0A%20%20%20%20%22Pened%C3%A8s%22%2C%0A%20%20%20%20%22Comarques%20Centrals%22%0A%20%20)
```

Descodificat:

```
SELECT
  `municipi`,
  `any`,
  `poblaci`,
  `establiments`,
  `densitat_comercial_est_1`
WHERE
  caseless_one_of(
    `mbit_territorial`,
    "Metropolità",
    "Penedès",
    "Comarques Centrals"
  )
ORDER BY `:id` ASC NULL LAST
LIMIT 4000
OFFSET 0
```

Aquest endpoint està filtrat per any, cal canviar l'any per cada cas. (fixeu-vos en el placeholder `[[[year]]]`)

```
https://analisi.transparenciacatalunya.cat/resource/2dhj-q3r8.json?$query=SELECT%0A%20%20%60municipi%60%2C%0A%20%20%60any%60%2C%0A%20%20%60poblaci%60%2C%0A%20%20%60establiments%60%2C%0A%20%20%60densitat_comercial_est_1%60%0AWHERE%0A%20%20(%60any%60%20IN%20(%22[[[year]]]%22))%0A%20%20AND%20caseless_one_of(%0A%20%20%20%20%60mbit_territorial%60%2C%0A%20%20%20%20%22Metropolit%C3%A0%22%2C%0A%20%20%20%20%22Pened%C3%A8s%22%2C%0A%20%20%20%20%22Comarques%20Centrals%22%0A%20%20)
```

Primer cal obtenir els anys perquè sino hi ha massa dades:

```
https://analisi.transparenciacatalunya.cat/resource/2dhj-q3r8.json?$query=SELECT%20%60any%60%20GROUP%20BY%20%60any%60%20ORDER%20BY%20%60any%60%20DESC%20NULL%20LAST
```

- Notes: la dada la tenim ja calculada a `densitat_comercial_est_1` però per normalitzar les dades importem el nombre d'establiments (`establiments`) i la població per aquell any (`poblaci`). Un problema d'aquesta API és que NO tenim el codi dels municipis.
- **value**: Nombre d’establiments comercials
- **value2**: Nombre d'habitants
- **calculation**: `value` \* 1000 / `value2`

### 8.9

Reforçar les polítiques de promoció d'un turisme sostenible, de proximitat, qualitat i que creï ocupació i promogui la cultura i els productes locals.

#### 8.9.1 Pressió turística [FET]

**Places hoteleres**

- URL principal (per saber els anys disponibles): https://www.idescat.cat/pub/?id=turall&n=6031&f=ssv
- URL província de Barcelona: https://www.idescat.cat/pub/?id=turall&n=6031&geo=prov%3A08&f=ssv
- URL comarques: https://www.idescat.cat/pub/?id=turall&n=6031&geo=com&t=[[[year]]]00&f=ssv
- URL municipis: https://www.idescat.cat/pub/?id=turall&n=6031&geo=mun&t=[[[year]]]00&f=ssv

**Càmpings**

- URL principal (per saber els anys disponibles): https://www.idescat.cat/pub/?id=turall&n=6036&f=ssv
- URL província de Barcelona: https://www.idescat.cat/pub/?id=turall&n=6036&geo=prov%3A08&f=ssv
- URL comarques: https://www.idescat.cat/pub/?id=turall&n=6036&geo=com&t=[[[year]]]00&f=ssv
- URL municipis: https://www.idescat.cat/pub/?id=turall&n=6036&geo=mun&t=[[[year]]]00&f=ssv

**Turisme rural**

- URL principal (per saber els anys disponibles): https://www.idescat.cat/pub/?id=turall&n=6039&f=ssv
- URL província de Barcelona: https://www.idescat.cat/pub/?id=turall&n=6039&geo=prov%3A08&f=ssv
- URL comarques: https://www.idescat.cat/pub/?id=turall&n=6039&geo=com&t=[[[year]]]00&f=ssv
- URL municipis: https://www.idescat.cat/pub/?id=turall&n=6039&geo=mun&t=[[[year]]]00&f=ssv

**Apartaments turístics**

- URL principal (per saber els anys disponibles): https://www.idescat.cat/pub/?id=turall&n=16721&f=ssv
- URL província de Barcelona: https://www.idescat.cat/pub/?id=turall&n=16721&geo=prov%3A08&f=ssv
- URL comarques: https://www.idescat.cat/pub/?id=turall&n=16721&geo=com&t=[[[year]]]00&f=ssv
- URL municipis: https://www.idescat.cat/pub/?id=turall&n=16721&geo=mun&t=[[[year]]]00&f=ssv

**Habitatges d'ús turístic**

- URL principal (per saber els anys disponibles): https://www.idescat.cat/pub/?id=turall&n=16722&f=ssv
- URL província de Barcelona: https://www.idescat.cat/pub/?id=turall&n=16722&geo=prov%3A08&f=ssv
- URL comarques: https://www.idescat.cat/pub/?id=turall&n=16722&geo=com&t=[[[year]]]00&f=ssv
- URL municipis: https://www.idescat.cat/pub/?id=turall&n=16722&geo=mun&t=[[[year]]]00&f=ssv

- URL secundària: POBLACIÓ_TOTAL

- **value**: total de places turístiques
- **value2**: POBLACIO_TOTAL
- **calculation**: value \* 10000 / value2

## 9

### 9.5

Augmentar la investigació científica i millorar la capacitat tecnològica dels sectors industrials.

#### 9.5.2 Llocs de treball indústria [FET]

(potser el mes canvia, he posat desembre de moment)

**Règim general**

- URL principal (per saber els anys disponibles): https://www.idescat.cat/pub/?id=afic&n=14983&geo=cat&f=ssv
- URL província de Barcelona: https://www.idescat.cat/pub/?id=afic&n=14983&geo=prov%3A08&f=ssv
- URL comarques: https://www.idescat.cat/pub/?id=afic&n=14983&geo=com&t=[[[year]]]04%3AP&f=ssv
- URL municipis: https://www.idescat.cat/pub/?id=afic&n=14983&geo=mun&t=[[[year]]]04%3AP&f=ssv

**Autònoms**

- URL principal (per saber els anys disponibles): https://www.idescat.cat/pub/?id=afic&n=14995&geo=cat&f=ssv
- URL província de Barcelona: https://www.idescat.cat/pub/?id=afic&n=14995&geo=prov%3A08&f=ssv
- URL comarques: https://www.idescat.cat/pub/?id=afic&n=14995&geo=com&t=[[[year]]]04%3AP&f=ssv
- URL municipis: https://www.idescat.cat/pub/?id=afic&n=14995&geo=mun&t=[[[year]]]04%3AP&f=ssv

- **value**: indústria règim general + indústria autònoms
- **value2**: total règim general + total autònoms
- **calculation**: value \* 100 / value2

## 10

### 10.1

Aconseguir progressivament un creixement dels ingressos del 40% més pobre de la població del territori a una taxa superior a la mitjana nacional.

#### 10.1.1 % Població amb ingressos < 40% [FET]

Percentatge de població que viu en unitats de consum amb una renda disponible inferior al 40% de la mitjana.

- FONT: INE
- URL:

```
https://servicios.ine.es/wstempus/js/ES/datos_tabla/30901?tv=19:&tv=848:322970&tv=18:451
```

- **Notes**: l'API ens dóna directament el percentatge de la població amb ingressos < 40 %. Primer haurem de normalitzar les dades (multiplicar per la població total).
- **value**: població amb ingressos < 40 %.
- **value2**: [POBLACIO_TOTAL](#població-total)
- **calculation**: value \* 100 / value2

#### 10.1.2 Índex Gini [FET]

Grau de desigualtat en una distribució d'una variable contínua. S'obté a partir de la suma de les diferències absolutes entre cada parell de rendes de la distribució.

- FONT: INE
- URL:

```
https://servicios.ine.es/wstempus/js/ES/datos_tabla/37686?tv=19:&tv=482:382445
```

- **value**: índex Gini.
- **value2**: `null`
- **calculation**: value

#### 10.1.3 Desigualtat per Renda [FET]

Desigualtat en la distribució a través de ràtios entre centils, que s'interpreta com la renda que s'obté per al quintil superior (és a dir, el 20% de la població amb un nivell econòmic més alt) en relació amb la del quintil inferior.

- FONT: INE
- URL:

```
https://servicios.ine.es/wstempus/js/ES/datos_tabla/37686?tv=19:&tv=482:382446
```

- **value**: distribució de la renda P80/P20
- **value2**: `null`
- **calculation**: value

#### 10.1.4 Renda per habitant [FET]

Relació entre el total de la renda del municipi i/o àmbit seleccionat vers la població total del municipi.

- FONT: IDESCAT
- URL:

```
https://api.idescat.cat/taules/v2/rfdbc/13301/14148/mun/data?indicator=PER_CAPITA_EUR
```

- Notes:
- **value**: renda familiar disponible bruta per habitant (€)
- **value2**: `null`
- **calculation**: value

## 11

### 11.1

Aconseguir progressivament un creixement dels ingressos del 40% més pobre de la població del territori a una taxa superior a la mitjana nacional.

#### 11.1.1 Esforç econòmic lloguer [REFER]

Mitjana del preu anual del lloguer en relació amb la renda bruta familiar

- FONT: DO_IDESCAT
- URL principal:

```
https://analisi.transparenciacatalunya.cat/resource/qww9-bvhh.json?periode=gener-desembre&$limit=10000&any=
```

Millor:

```
SELECT `any`, `renda`, `codi_territorial`
WHERE
  caseless_starts_with(`codi_territorial`, "08")
  AND caseless_one_of(`periode`, "gener-desembre")
ORDER BY `:id` ASC NULL LAST
LIMIT 5000
OFFSET 0
```

```
https://analisi.transparenciacatalunya.cat/resource/qww9-bvhh.json?$query=SELECT%20%60any%60%2C%20%60renda%60%2C%20%60codi_territorial%60%0AWHERE%0A%20%20caseless_starts_with(%60codi_territorial%60%2C%20%2208%22)%0A%20%20AND%20caseless_one_of(%60periode%60%2C%20%22gener-desembre%22)%20limit%205000
```

- URL secundaria:

```
https://api.idescat.cat/taules/v2/rfdbc/13301/14148/mun/data?indicator=PER_CAPITA_EUR&concept=GROSS_INCOME&YEAR=
```

A ambdues URLs cal afegir l'any en el que es vol consultar les dades.

- Notes: La primera API dona la renda destinada al lloguer (mensual) i la segona API dona la renda bruta (anual) familiar. **ALERTA**: hi ha municipis que no tenen la dada del lloguer degut al baix nombre d'habitatges llogats (49 municipis al 2023). No obstant, sí que tenim el `tram_preus`.
- **[ACLARIR]** La primera API parla de import del lloguer mensual mitjà, mentre que la segona API parla de renda bruta familiar disponible per habitant.
  Entenem que "renda familiar disponible bruta / per habitant (€)" ja està normalitzada per habitant, precisament.
- **value**: Import del lloguer mensual mitjà. Dades anonimitzades per municipis amb menys de 6 habitatges registrats
- **value2**: renda familiar disponible bruta / per habitant (€)
- **calculation**: value _ 12 _ 100 / value2

### 11.3

Aconseguir un model urbà inclusiu i sostenible a través d'una planificació estratègica concertada amb el territori i amb la participació de la ciutadania.

#### 11.3.1 Percentatge de sòl artificialitztat [FET]

Percentatge de sòl artificialitzat (real o potencial). Percentatge de sòl destinat a usos urbans o infraestructures respecte la superfície total de la unitat territorial.

- FONT: DO
- URL: https://analisi.transparenciacatalunya.cat/Urbanisme-infraestructures/Dades-del-mapa-urban-stic-de-Catalunya/epsm-zskb/about_data

API endpoint:

```
https://analisi.transparenciacatalunya.cat/resource/epsm-zskb.json?$query=SELECT%0A%20%20%60any%60%2C%0A%20%20%60codi_ine_5_txt%60%2C%0A%20%20%60superficie_ha%60%2C%0A%20%20%60_05_su%60%2C%0A%20%20%60_05_perce_su%60%0AWHERE%0A%20%20caseless_starts_with(%60codi_ine_5_txt%60%2C%20%2208%22)%20AND%20(%60any%60%20IN%20(%222019%22))
```

Descodificat:

```
SELECT
  `any`,
  `codi_ine_5_txt`,
  `superficie_ha`,
  `_05_su`,
  `_05_perce_su`
WHERE caseless_starts_with(`codi_ine_5_txt`, "08")
ORDER BY `:id` ASC NULL LAST
LIMIT 4000
OFFSET 0
```

Aquest endpoint està filtrat per any, cal canviar l'any per cada cas. (fixeu-vos en el placeholder `[[[year]]]`)

```
https://analisi.transparenciacatalunya.cat/resource/epsm-zskb.json?$query=SELECT%0A%20%20%60any%60%2C%0A%20%20%60codi_ine_5_txt%60%2C%0A%20%20%60superficie_ha%60%2C%0A%20%20%60_05_su%60%2C%0A%20%20%60_05_perce_su%60%0AWHERE%0A%20%20caseless_starts_with(%60codi_ine_5_txt%60%2C%20%2208%22)%20AND%20(%60any%60%20IN%20(%22[[[year]]]%22))
```

Primer cal obtenir els anys perquè sino hi ha massa dades:

```
https://analisi.transparenciacatalunya.cat/resource/epsm-zskb.json?$query=SELECT%20%60any%60%20GROUP%20BY%20%60any%60%20ORDER%20BY%20%60any%60%20DESC%20NULL%20LAST
```

- Notes: d'aquesta API podem obtenir tant el percentatge ja calculat (`_05_perce_su`) com les superfícies (total del municipi `superficie_ha` i sòl urbà `_05_su` ).
- **value**: superfície de sòl urbà.
- **value2**: superfície total del municipi
- **calculation**: value \* 100 / value2

### 11.4

Redoblar els esforços per protegir i salvaguardar el patrimoni cultural i natural.

#### 11.4.1 Superfície (m2) zona verda [FET]

Relació entre volum de població municipal i espai qualificat de zona verda o espai lliure en el sòl urbà del municipi.

- FONT: DO
- URL (igual que 11.3):

```
https://analisi.transparenciacatalunya.cat/resource/epsm-zskb.json
```

Millor:

```
SELECT
  `any`,
  `codi_ine_5_txt`,
  `superficie_ha`,
  `_19_zverdes_habt`,
  `poblacio_padro`
WHERE caseless_starts_with(`codi_ine_5_txt`, "08")
ORDER BY `:id` ASC NULL LAST
LIMIT 4000
OFFSET 0
```

Aquest endpoint està filtrat per any, cal canviar l'any per cada cas. (fixeu-vos en el placeholder `[[[year]]]`)

```
https://analisi.transparenciacatalunya.cat/resource/epsm-zskb.json?$query=SELECT%0A%20%20%60any%60%2C%0A%20%20%60codi_ine_5_txt%60%2C%0A%20%20%60superficie_ha%60%2C%0A%20%20%60poblacio_padro%60%2C%0A%20%20%60_19_zverdes_habt%60%0AWHERE%0A%20%20(%60any%60%20IN%20(%22[[[year]]]%22))%20AND%20caseless_starts_with(%60codi_ine_5_txt%60%2C%20%2208%22)
```

Primer cal obtenir els anys perquè sino hi ha massa dades:

```
https://analisi.transparenciacatalunya.cat/resource/epsm-zskb.json?$query=SELECT%20%60any%60%20GROUP%20BY%20%60any%60%20ORDER%20BY%20%60any%60%20DESC%20NULL%20LAST
```

- Notes: d'aquesta API podem obtenir directament la superfície de zona verda per habitant (`_19_zverdes_habt`) i a més la població que hi havia al municipi a aquell any (`poblacio_padro`). El que fem es desnormalitzar, és a dir, multipliquem la superfície de zona verda per habitant per la població. Guardem aquesta dada, i també la població.
- **value**: superfície de zona verda total
- **value2**: població total
- **calculation**: value / value2

### 11.7

Proporcionar accés universal a més zones verdes i espais públics segurs, inclusius i accessibles, amb especial èmfasi a les dones, els infants, les persones grans i les persones amb discapacitat.

#### 11.7.1 Superfície (m2) zona verda [FET]

Igual que [11.4.1](#1141-superfície-m2-zona-verda)

## 12

### 12.5

Disminuir substancialment la generació de residus mitjançant polítiques de prevenció, reducció, reciclatge i reutilització.

#### 12.5.1 Taxa recollida selectiva [FET]

Percentatge de recollida selectiva de residus domèstics respecte del total produït.

- FONT: DO
- URL:

```
https://analisi.transparenciacatalunya.cat/resource/69zu-w48s.json
```

Millor:

```
https://analisi.transparenciacatalunya.cat/resource/69zu-w48s.json?$query=SELECT
  `any`,
  `codi_municipi`,
  `r_s_r_m_total`,
  `total_recollida_selectiva`,
  `generaci_residus_municipal`
WHERE
  caseless_one_of(`any`, "2000")
  AND caseless_starts_with(`codi_municipi`, "08")
```

Aquest endpoint està filtrat per any, cal canviar l'any per cada cas.

```
https://analisi.transparenciacatalunya.cat/resource/69zu-w48s.json?$query=SELECT%0A%20%20%60any%60%2C%0A%20%20%60codi_municipi%60%2C%0A%20%20%60r_s_r_m_total%60%2C%0A%20%20%60total_recollida_selectiva%60%2C%0A%20%20%60generaci_residus_municipal%60%0AWHERE%0A%20%20caseless_one_of(%60any%60%2C%20%222000%22)%0A%20%20AND%20caseless_starts_with(%60codi_municipi%60%2C%20%2208%22)
```

Primer cal obtenir els anys perquè sino hi ha massa dades:

```
https://analisi.transparenciacatalunya.cat/resource/69zu-w48s.json?$query=SELECT%20%60any%60%20GROUP%20BY%20%60any%60%20ORDER%20BY%20%60any%60%20DESC%20NULL%20LAST
```

- Notes: d'aquesta API podem obtenir directament el percentatge de recollida selectiva respecte al total de residus municipals (`r_s_r_m_total`). A més, també podem obtenir el total de recollida selectiva (en tones?) (`total_recollida_selectiva`) i el total dels residus en tones (`generaci_residus_municipal`).
- **value**: total recollida selectiva per municipi en tones
- **value2**: total de residus en tones
- **calculation**: value \* 100 / value2

#### 12.5.2 Residus domèstics en kg/hab/any [FET]

Producció de residus domèstics en kg/hab/any.

- FONT: DO
- URL:

```
https://analisi.transparenciacatalunya.cat/resource/69zu-w48s.json
```

Millor:

```
SELECT
  `any`,
  `codi_municipi`,
  `generaci_residus_municipal`,
  `poblaci`,
  `kg_hab_any`
WHERE caseless_starts_with(`codi_municipi`, "08")
ORDER BY `:id` ASC NULL LAST
LIMIT 8000
OFFSET 0
```

```
https://analisi.transparenciacatalunya.cat/resource/69zu-w48s.json?$query=SELECT%0A%20%20%60any%60%2C%0A%20%20%60codi_municipi%60%2C%0A%20%20%60generaci_residus_municipal%60%2C%0A%20%20%60poblaci%60%2C%0A%20%20%60kg_hab_any%60%0AWHERE%20caseless_starts_with(%60codi_municipi%60%2C%20%2208%22)%20limit%208000
```

- Notes: `kg_hab_any`. Per normalitzar les dades podem calcular-ho a partir del total de residus del municipi (`generaci_residus_municipal`) i de la població per aquell any (`poblaci`).
- **value**: total de residus del municipi en tones (`generaci_residus_municipal`)
- **value2**: població del municipi
- **calculation**: value \* 1000 / value2

### 12.8

Assegurar que el conjunt de la ciutadania tingui la informació i conscienciació pertinents per assolir el desenvolupament i els estils de vida sostenibles.

#### 12.8.1 Nombre de socis de cooperatives per habitant

Nombre de socis de cooperatives registrades per habitant.

- FONT: DO
- URL:

```
https://analisi.transparenciacatalunya.cat/resource/euku-fzbx.json?prov_ncia=BARCELONA
```

Millor:

```
SELECT `data_d_inscripci`, `municipi`, `total_socis_inicials`
WHERE caseless_one_of(`prov_ncia`, "BARCELONA")
ORDER BY `:id` ASC NULL LAST
LIMIT 5000
OFFSET 0
```

```
https://analisi.transparenciacatalunya.cat/resource/euku-fzbx.json?$query=SELECT%20%60data_d_inscripci%60%2C%20%60municipi%60%2C%20%60total_socis_inicials%60%0AWHERE%20caseless_one_of(%60prov_ncia%60%2C%20%22BARCELONA%22)%20limit%205000
```

- Notes: `total_socis_inicials`. No hi ha codi de municipi, hem de filtrar pel nom.
- **value**: nombre de socis
- **value2**: [POBLACIO_TOTAL](#població-total)
- **calculation**: value \* 100 / value2

## 15

### 15.1

Vetllar per la conservació, el restabliment i l’ús sostenible dels ecosistemes terrestres i d'aigua dolça, en particular els boscos, els aiguamolls, les muntanyes i les zones àrides.

#### 15.1.1 % superfície protecció local

- FONT: DO
- URL:

```
SELECT `any`, `codi_ine_5_txt`, `superficie_ha`, `_16_n2_snu`
WHERE caseless_starts_with(`codi_ine_5_txt`, "08")
ORDER BY `:id` ASC NULL LAST
LIMIT 100
OFFSET 0
```

```
https://analisi.transparenciacatalunya.cat/resource/epsm-zskb.json?$query=SELECT%20%60any%60%2C%20%60codi_ine_5_txt%60%2C%20%60superficie_ha%60%2C%20%60_16_n2_snu%60%0AWHERE%20caseless_starts_with(%60codi_ine_5_txt%60%2C%20%2208%22)
```

- **value**: columna \_16_n2_snu
- **value2**: columna superficie_ha
- **calculation**: value \* 100 / value2

#### 15.1.2 % superfície protecció reglada

- FONT: DO
- URL:

```
SELECT `any`, `codi_ine_5_txt`, `superficie_ha`, `_16_n3_snu`
WHERE caseless_starts_with(`codi_ine_5_txt`, "08")
ORDER BY `:id` ASC NULL LAST
LIMIT 100
OFFSET 0
```

```
https://analisi.transparenciacatalunya.cat/resource/epsm-zskb.json?$query=SELECT%20%60any%60%2C%20%60codi_ine_5_txt%60%2C%20%60superficie_ha%60%2C%20%60_16_n3_snu%60%0AWHERE%20caseless_starts_with(%60codi_ine_5_txt%60%2C%20%2208%22)
```

- **value**: columna \_16_n3_snu
- **value2**: columna superficie_ha
- **calculation**: value \* 100 / value2

## 16

### 16.7

Garantir l'adopció de decisions inclusives, participatives i representatives que responguin a les necessitats de la ciutadania.

#### 16.7.1 Participació eleccions municipals [FET]

Nombre d'electors que realment exerceixen el seu dret de vot. S'expressa en el percentatge del nombre de votants (inclosos els vots en blanc i els nuls) respecte al nombre total d'electors.

- FONT: DO
- URL:

```
https://analisi.transparenciacatalunya.cat/resource/irrv-2mfc.json?$where=contains(nom_eleccio,%20'Eleccions%20Municipals')&nom_nivell_territorial=Municipi&$limit=11000
```

Millor: (agrupem per id_eleccio i territori_codi perque hem vist, en dades antigues, que a vegades les dades venen segmentades per seccio, mesa, etc.)

```
SELECT
  `id_eleccio`,
  `territori_codi`,
  sum(`cens_electoral`) AS `sum_cens_electoral`,
  sum(`votants`) AS `sum_votants`,
  sum(`padro`) AS `sum_padro`
WHERE
  caseless_one_of(`id_nivell_territorial`, "MU")
  AND contains(`nom_eleccio`, "Eleccions Municipals")
GROUP BY `id_eleccio`, `territori_codi`
HAVING caseless_starts_with(`territori_codi`, "08")
ORDER BY `id_eleccio` ASC NULL FIRST, `territori_codi` ASC NULL FIRST
LIMIT 4000
OFFSET 0
```

(reemplaçar `[[[year]]]` per l'any que es vol consultar, per exemple `M20191`)

```
https://analisi.transparenciacatalunya.cat/resource/irrv-2mfc.json?$query=SELECT%0A%20%20%60id_eleccio%60%2C%0A%20%20%60territori_codi%60%2C%0A%20%20sum(%60votants%60)%20AS%20%60sum_votants%60%2C%0A%20%20sum(%60padro%60)%20AS%20%60sum_padro%60%2C%0A%20%20sum(%60cens_electoral%60)%20AS%20%60sum_cens_electoral%60%0AWHERE%0A%20%20caseless_one_of(%60id_eleccio%60%2C%20%22[[[year]]]%22)%0A%20%20AND%20caseless_contains(%60nom_eleccio%60%2C%20%22Eleccions%20Municipals%22)%0A%20%20AND%20(caseless_one_of(%60id_nivell_territorial%60%2C%20%22MU%22)%0A%20%20%20%20%20%20%20%20%20AND%20caseless_starts_with(%60territori_codi%60%2C%20%2208%22))%0AGROUP%20BY%20%60id_eleccio%60%2C%20%60territori_codi%60%0AORDER%20BY%20%60id_eleccio%60%20DESC%20NULL%20LAST
```

Primer cal obtenir els anys (id_eleccio) perquè sino hi ha massa dades:

```
https://analisi.transparenciacatalunya.cat/resource/irrv-2mfc.json?$query=SELECT%0A%20%20%60id_eleccio%60%2C%0A%20%20%60territori_codi%60%2C%0A%20%20sum(%60cens_electoral%60)%20AS%20%60sum_cens_electoral%60%2C%0A%20%20sum(%60votants%60)%20AS%20%60sum_votants%60%2C%0A%20%20sum(%60padro%60)%20AS%20%60sum_padro%60%0AWHERE%0A%20%20caseless_one_of(%60id_nivell_territorial%60%2C%20%22MU%22)%0A%20%20AND%20contains(%60nom_eleccio%60%2C%20%22Eleccions%20Municipals%22)%0AGROUP%20BY%20%60id_eleccio%60%2C%20%60territori_codi%60%0AHAVING%20caseless_starts_with(%60territori_codi%60%2C%20%2208%22)%0ALIMIT%204000
```

- Notes: Nombre total d'electors: `cens_electoral`. Nombre de votants: `votants`. El `padro` té la població total del municipi.
- **value**: `votants`
- **value2**: `cens_electoral`
- **calculation**: value \* 100 / value2

## 17

### 17.4

#### 17.4.1 Deute viu per habitant [FET]

El deute viu es calcula tenint en compte les operacions de risc en crèdits financers, valors de renda fixa i préstecs o crèdits transferits a tercers. No inclou el deute comercial de les entitats locals, és a dir, la que mantenen amb els seus proveïdors. De forma simplificada, podem dir que és la quantitat de diners que l'Ajuntament deu via crèdit.

- FONT: DO
- URL:

```
https://analisi.transparenciacatalunya.cat/resource/c9ag-cye6.json
```

Millor:

```
SELECT `any`, `deute_viu`, `codi_ens`, `cens`
WHERE starts_with(`codi_ens`, "8") AND (`codi_ens` > "800180000")
ORDER BY `:id` ASC NULL LAST
LIMIT 5000
OFFSET 0
```

Aquest endpoint està filtrat per any, cal canviar l'any per cada cas.

```
https://analisi.transparenciacatalunya.cat/resource/c9ag-cye6.json?$query=SELECT%20%60any%60%2C%20%60deute_viu%60%2C%20%60codi_ens%60%2C%20%60cens%60%0AWHERE%0A%20%20caseless_one_of(%60any%60%2C%20%222023%22)%0A%20%20AND%20starts_with(%60codi_ens%60%2C%20%228%22)%20AND%20(%60codi_ens%60%20%3E%20%22800180000%22)%20limit%205000
```

Primer cal obtenir els anys perquè sino hi ha massa dades:

```
https://analisi.transparenciacatalunya.cat/resource/c9ag-cye6.json?$query=SELECT%20%60any%60%20GROUP%20BY%20%60any%60%20ORDER%20BY%20%60any%60%20DESC%20NULL%20LAST
```

```
https://analisi.transparenciacatalunya.cat/resource/c9ag-cye6.json?$query=SELECT%20%60any%60%2C%20%60deute_viu%60%2C%20%60codi_ens%60%2C%20%60cens%60%0AWHERE%20starts_with(%60codi_ens%60%2C%20%228%22)%20AND%20(%60codi_ens%60%20%3E%20%22800180000%22)%20limit%205000
```

- Notes: el codi del municipi (`ine6` sense el primer 0) es troba dins l'string de `codi_ens`.
- **value**: `deute_viu`
- **value2**: `cens`
- **calculation**: value / value2

# Annex

## Població total

La població total per municipis la podem trobar a Dades Obertes.

```
SELECT `codi_10`, `nom_ens`, `any`, `total`, `homes`, `dones`
WHERE caseless_starts_with(`codi_10`, "08")
ORDER BY `any` ASC NULL LAST
LIMIT 100
OFFSET 0
```

```
https://analisi.transparenciacatalunya.cat/resource/x5sz-niat.json?$query=SELECT%20%60codi_10%60%2C%20%60nom_ens%60%2C%20%60any%60%2C%20%60total%60%2C%20%60homes%60%2C%20%60dones%60%0AWHERE%20caseless_starts_with(%60codi_10%60%2C%20%2208%22)
```

Els valors pels que podem filtrar són:

- any: des del 1990 fins al 2023 (per algun motiu, l'any 1997 no hi és).
- codi_10: comença pel codi ine6 del municipi.

## Població de 16 a 64 anys

### Idescat

https://api.idescat.cat/taules/v2/censph/540/19948/mun/data?SEX=total&AGE=Y016_064&YEAR=[[[year]]]
Els anys disponibles per a la població de 16 a 64 són: 1975, 1981, 1986, 1991, 1996, 2001, 2011, 2021, 2022, 2023.

### Idescat 2 No té la dada de 2023. S'han de sumar els valors per a cada edat.

- 2000-2013: https://api.idescat.cat/taules/v2/pmh/1180/1063/mun/data?AGE=Y016,Y017,Y018,Y019,Y020,Y021,Y022,Y023,Y024,Y025,Y026,Y027,Y028,Y029,Y030,Y031,Y032,Y033,Y034,Y035,Y036,Y037,Y038,Y039,Y040,Y041,Y042,Y043,Y044,Y045,Y046,Y047,Y048,Y049,Y050,Y051,Y052,Y053,Y054,Y055,Y056,Y057,Y058,Y059,Y060,Y061,Y062,Y063,Y064&SEX=total&MUN=[[[municipi]]]&YEAR=[[[year]]]
- 2014-2022: https://api.idescat.cat/taules/v2/pmh/1180/8078/mun/data?AGE=Y016,Y017,Y018,Y019,Y020,Y021,Y022,Y023,Y024,Y025,Y026,Y027,Y028,Y029,Y030,Y031,Y032,Y033,Y034,Y035,Y036,Y037,Y038,Y039,Y040,Y041,Y042,Y043,Y044,Y045,Y046,Y047,Y048,Y049,Y050,Y051,Y052,Y053,Y054,Y055,Y056,Y057,Y058,Y059,Y060,Y061,Y062,Y063,Y064&SEX=total&MUN=[[[municipi]]]&YEAR=[[[year]]]

### INE No té dada de 2023. S'han de sumar els valors per a cada edat.

https://servicios.ine.es/wstempus/js/ES/datos_tabla/33721?tv=19:[[[municipi]]]&tv=18:451&tv=355:15335&tv=355:15336&tv=355:15337&tv=355:15338&tv=355:15339&tv=355:15340&tv=355:15341&tv=355:15342&tv=355:15343&tv=355:15344&tv=355:15345&tv=355:15346&tv=355:15347&tv=355:15348&tv=355:15349&tv=355:15350&tv=355:15351&tv=355:15352&tv=355:15353&tv=355:15354&tv=355:15355&tv=355:15356&tv=355:15357&tv=355:15358&tv=355:15359&tv=355:15360&tv=355:15361&tv=355:15362&tv=355:15363&tv=355:15364&tv=355:15365&tv=355:15366&tv=355:15367&tv=355:15368&tv=355:15369&tv=355:15370&tv=355:15371&tv=355:15372&tv=355:15373&tv=355:15374&tv=355:15375&tv=355:15376&tv=355:15377&tv=355:15378&tv=355:15379&tv=355:15380&tv=355:15381&tv=355:15382&tv=355:15383&date=[[[year]]]0101
