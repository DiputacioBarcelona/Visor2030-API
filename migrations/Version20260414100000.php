<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Corrects the previous migration (Version20260409100000):
 * 'Rural especial atenció' and 'Incorporat' should map to 'rural', not 'no-rural'.
 *
 * Uses the original ruralitat table and m.ruralitat_id to identify affected
 * municipalities, then remaps their aggregation link from 'no-rural' to 'rural'.
 */
final class Version20260414100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix ruralitat remap: move rural-especial-atencio and incorporat municipalities from no-rural to rural';
    }

    public function up(Schema $schema): void
    {
        // Link affected municipalities to 'rural'.
        // INSERT IGNORE handles municipalities already linked to 'rural'.
        $this->addSql("
            INSERT IGNORE INTO municipality_aggregation (municipality_id, aggregation_id)
            SELECT m.id, (SELECT id FROM aggregation WHERE slug = 'rural')
            FROM municipality m
            JOIN ruralitat r ON m.ruralitat_id = r.id
            WHERE r.name IN ('Rural especial atenció', 'Incorporat')
        ");

        // Remove the incorrect 'no-rural' link for those municipalities.
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

    public function down(Schema $schema): void
    {
        // Revert: link affected municipalities back to 'no-rural'.
        $this->addSql("
            INSERT IGNORE INTO municipality_aggregation (municipality_id, aggregation_id)
            SELECT m.id, (SELECT id FROM aggregation WHERE slug = 'no-rural')
            FROM municipality m
            JOIN ruralitat r ON m.ruralitat_id = r.id
            WHERE r.name IN ('Rural especial atenció', 'Incorporat')
        ");

        // Remove the 'rural' link for those municipalities.
        $this->addSql("
            DELETE FROM municipality_aggregation
            WHERE aggregation_id = (SELECT id FROM aggregation WHERE slug = 'rural')
              AND municipality_id IN (
                  SELECT m.id FROM municipality m
                  JOIN ruralitat r ON m.ruralitat_id = r.id
                  WHERE r.name IN ('Rural especial atenció', 'Incorporat')
              )
        ");
    }
}
