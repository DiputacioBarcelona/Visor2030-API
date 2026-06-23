<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Aggregations feature — Step 1.3 revision
 *
 * Collapses 'Rural especial atenció' and 'Incorporat' ruralitat aggregations
 * into 'No rural'. Municipalities previously linked to either of those two
 * aggregations are remapped to 'No rural', then the obsolete rows are deleted.
 */
final class Version20260409100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Collapse ruralitat aggregations: remap rural-especial-atencio and incorporat to no-rural, then delete them';
    }

    public function up(Schema $schema): void
    {
        // Remap municipalities linked to 'rural-especial-atencio' or 'incorporat'
        // to 'no-rural'. INSERT IGNORE handles the case where a municipality
        // was already linked to 'no-rural' (composite PK prevents duplicates).
        $this->addSql("
            INSERT IGNORE INTO municipality_aggregation (municipality_id, aggregation_id)
            SELECT ma.municipality_id, (SELECT id FROM aggregation WHERE slug = 'no-rural')
            FROM municipality_aggregation ma
            WHERE ma.aggregation_id IN (
                SELECT id FROM aggregation WHERE slug IN ('rural-especial-atencio', 'incorporat')
            )
        ");

        // Remove the now-obsolete join table entries
        $this->addSql("
            DELETE FROM municipality_aggregation
            WHERE aggregation_id IN (
                SELECT id FROM aggregation WHERE slug IN ('rural-especial-atencio', 'incorporat')
            )
        ");

        // Drop aggregation_value rows referencing the obsolete aggregations
        $this->addSql("
            DELETE FROM aggregation_value
            WHERE aggregation_id IN (
                SELECT id FROM aggregation WHERE slug IN ('rural-especial-atencio', 'incorporat')
            )
        ");

        // Drop the two obsolete aggregation rows
        $this->addSql("DELETE FROM aggregation WHERE slug IN ('rural-especial-atencio', 'incorporat')");
    }

    public function down(Schema $schema): void
    {
        // Re-insert the two removed aggregation rows
        $this->addSql("INSERT INTO aggregation (name, slug, agg_group) VALUES ('Rural especial atenció', 'rural-especial-atencio', 'ruralitat')");
        $this->addSql("INSERT INTO aggregation (name, slug, agg_group) VALUES ('Incorporat', 'incorporat', 'ruralitat')");

        // Repopulate join table from the ruralitat FK for those two values
        $this->addSql("
            INSERT IGNORE INTO municipality_aggregation (municipality_id, aggregation_id)
            SELECT m.id, a.id
            FROM municipality m
            JOIN ruralitat r ON m.ruralitat_id = r.id
            JOIN aggregation a ON a.agg_group = 'ruralitat' AND a.name = r.name
            WHERE r.name IN ('Rural especial atenció', 'Incorporat')
        ");

        // Remove 'no-rural' from municipalities that originally belonged to those two groups
        $this->addSql("
            DELETE FROM municipality_aggregation
            WHERE aggregation_id = (SELECT id FROM aggregation WHERE slug = 'no-rural')
              AND municipality_id IN (
                  SELECT m.id FROM municipality m
                  JOIN ruralitat r ON m.ruralitat_id = r.id
                  WHERE r.name IN ('Rural especial atenció', 'Incorporat')
              )
        ");
    }
}
