<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260327105154 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE aggregation_value (id INT AUTO_INCREMENT NOT NULL, aggregation_id INT NOT NULL, indicator_id INT NOT NULL, value DOUBLE PRECISION NOT NULL, value2 DOUBLE PRECISION DEFAULT NULL, subindicator INT DEFAULT NULL, year SMALLINT DEFAULT NULL, month SMALLINT DEFAULT NULL, unit VARCHAR(15) NOT NULL, sdg SMALLINT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_ECB029834A7F073 (aggregation_id), INDEX IDX_ECB02984402854A (indicator_id), INDEX idx_agg_year_indicator (year, indicator_id), INDEX idx_agg_subindicator (subindicator), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE aggregation_value ADD CONSTRAINT FK_ECB029834A7F073 FOREIGN KEY (aggregation_id) REFERENCES aggregation (id)');
        $this->addSql('ALTER TABLE aggregation_value ADD CONSTRAINT FK_ECB02984402854A FOREIGN KEY (indicator_id) REFERENCES indicator (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE aggregation_value DROP FOREIGN KEY FK_ECB029834A7F073');
        $this->addSql('ALTER TABLE aggregation_value DROP FOREIGN KEY FK_ECB02984402854A');
        $this->addSql('DROP TABLE aggregation_value');
    }
}
