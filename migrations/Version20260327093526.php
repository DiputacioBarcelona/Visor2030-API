<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Aggregations feature — Step 1.3 Phase B & C
 *
 * Phase B: Seed aggregation rows from ruralitat, ubicacio, and boolean flags.
 *          territorial_region is skipped — table is empty in the live DB.
 *
 * Phase C: Populate municipality_aggregation join table from existing FK columns
 *          and boolean flags on municipality.
 */
final class Version20260327093526 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed aggregation rows and populate municipality_aggregation join table';
    }

    public function up(Schema $schema): void
    {
        // --- Phase B: Seed aggregation rows ---

        // Group: ruralitat (4 values from live DB)
        $this->addSql("INSERT INTO aggregation (name, slug, agg_group) VALUES ('Rural', 'rural', 'ruralitat')");
        $this->addSql("INSERT INTO aggregation (name, slug, agg_group) VALUES ('Rural especial atenció', 'rural-especial-atencio', 'ruralitat')");
        $this->addSql("INSERT INTO aggregation (name, slug, agg_group) VALUES ('Incorporat', 'incorporat', 'ruralitat')");
        $this->addSql("INSERT INTO aggregation (name, slug, agg_group) VALUES ('No rural', 'no-rural', 'ruralitat')");

        // Group: ubicacio (3 values from live DB)
        $this->addSql("INSERT INTO aggregation (name, slug, agg_group) VALUES ('Litoral', 'litoral', 'ubicacio')");
        $this->addSql("INSERT INTO aggregation (name, slug, agg_group) VALUES ('Muntanya', 'muntanya', 'ubicacio')");
        $this->addSql("INSERT INTO aggregation (name, slug, agg_group) VALUES ('Interior', 'interior', 'ubicacio')");

        // Group: regional-flag (boolean columns on municipality)
        $this->addSql("INSERT INTO aggregation (name, slug, agg_group) VALUES ('Municipis Industrials', 'industrials', 'regional-flag')");
        $this->addSql("INSERT INTO aggregation (name, slug, agg_group) VALUES ('AMB', 'amb', 'regional-flag')");
        $this->addSql("INSERT INTO aggregation (name, slug, agg_group) VALUES ('RMB', 'rmb', 'regional-flag')");

        // --- Phase C: Populate municipality_aggregation join table ---

        // From ruralitat FK
        $this->addSql("
            INSERT INTO municipality_aggregation (municipality_id, aggregation_id)
            SELECT m.id, a.id
            FROM municipality m
            JOIN ruralitat r ON m.ruralitat_id = r.id
            JOIN aggregation a ON a.agg_group = 'ruralitat' AND a.name = r.name
        ");

        // From ubicacio FK
        $this->addSql("
            INSERT INTO municipality_aggregation (municipality_id, aggregation_id)
            SELECT m.id, a.id
            FROM municipality m
            JOIN ubicacio u ON m.ubicacio_id = u.id
            JOIN aggregation a ON a.agg_group = 'ubicacio' AND a.name = u.name
        ");

        // From is_industrial boolean
        $this->addSql("
            INSERT INTO municipality_aggregation (municipality_id, aggregation_id)
            SELECT m.id, (SELECT id FROM aggregation WHERE slug = 'industrials')
            FROM municipality m WHERE m.is_industrial = 1
        ");

        // From is_in_amb boolean
        $this->addSql("
            INSERT INTO municipality_aggregation (municipality_id, aggregation_id)
            SELECT m.id, (SELECT id FROM aggregation WHERE slug = 'amb')
            FROM municipality m WHERE m.is_in_amb = 1
        ");

        // From is_in_rmb boolean
        $this->addSql("
            INSERT INTO municipality_aggregation (municipality_id, aggregation_id)
            SELECT m.id, (SELECT id FROM aggregation WHERE slug = 'rmb')
            FROM municipality m WHERE m.is_in_rmb = 1
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM municipality_aggregation');
        $this->addSql('DELETE FROM aggregation');
    }
}
