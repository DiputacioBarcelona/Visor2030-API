# Visor 2030 — Backend

API REST per a la visualització d'indicadors dels **Objectius de Desenvolupament Sostenible (ODS)** de municipis catalans. Aquesta és la part *backend* del projecte Visor 2030.

- **Visor públic**: [https://visor2030.diba.cat/](https://visor2030.diba.cat/)
- **Documentació tècnica**: [https://visor2030-documentacio.diba.cat/](https://visor2030-documentacio.diba.cat/)

> El projecte sencer està format per:
> - **aquest repo** — API REST
> - [`Visor2030-Front`](https://github.com/DiputacioBarcelona/Visor2030-Front) — visualització pública (SPA)
> - [`Visor2030-Back`](https://github.com/DiputacioBarcelona/Visor2030-Back) — backoffice

## Tecnologia

- **PHP 8.2+**
- **Symfony 6.4** + **API Platform 4**
- **MySQL 8** (utf8mb4) amb Doctrine ORM
- Autenticació JWT (`lexik/jwt-authentication-bundle`)

## Instal·lació ràpida

```bash
# 1. Clonar
git clone https://github.com/DiputacioBarcelona/Visor2030-API.git
cd Visor2030-API

# 2. Instal·lar dependències
composer install

# 3. Configurar variables d'entorn
cp .env .env.local
# Edita .env.local i defineix DATABASE_URL, JWT_PASSPHRASE i CORS_ALLOW_ORIGIN

# 4. Generar claus JWT
php bin/console lexik:jwt:generate-keypair

# 5. Crear la BBDD i aplicar migracions
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 6. Carregar dades inicials (municipis, comarques, agrupacions)
php bin/console app:load-initial-data

# 7. Servidor de desenvolupament
symfony server:start
```

L'API estarà disponible a `https://localhost:8000/api`. La documentació interactiva (Swagger) es genera automàticament a `https://localhost:8000/api`.

## Comandes principals

```bash
# Importar dades d'un indicador
php bin/console app:run-etl-api 1.2.1

# Calcular agrupacions (sovint automàtic després d'una importació)
php bin/console app:calculate-aggregation-values

# Importar pressupostos municipals
php bin/console app:import-budgets 2024

# Exportar CSV per ODS
php bin/console app:export-csv 4
```

## Documentació tècnica

La documentació tècnica completa està publicada a **[https://visor2030-documentacio.diba.cat/](https://visor2030-documentacio.diba.cat/)**:

- Model de dades i esquema BBDD
- Pipeline ETL i com afegir indicadors nous
- Sistema d'agrupacions
- API i endpoints
- ODS sintètics i pressupostos

## Tests

```bash
php bin/phpunit
```

## Estil de codi

```bash
vendor/bin/php-cs-fixer fix
```

## Llicència

[AGPLv3](LICENSE)

## Crèdits

Desenvolupat per [OneTandem](https://onetandem.com) per a la [Diputació de Barcelona](https://www.diba.cat).
