<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250217155612 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE budget (id INT AUTO_INCREMENT NOT NULL, municipality_id INT NOT NULL, year INT NOT NULL, value DOUBLE PRECISION NOT NULL, program VARCHAR(6) NOT NULL, INDEX IDX_73F2F77BAE6F181C (municipality_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE budget ADD CONSTRAINT FK_73F2F77BAE6F181C FOREIGN KEY (municipality_id) REFERENCES municipality (id)');
        $this->addSql('DROP INDEX idx_date ON municipality_value');
        $this->addSql('DROP INDEX idx_year ON municipality_value');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE budget DROP FOREIGN KEY FK_73F2F77BAE6F181C');
        $this->addSql('DROP TABLE budget');
        $this->addSql('CREATE INDEX idx_date ON municipality_value (updated_at)');
        $this->addSql('CREATE INDEX idx_year ON municipality_value (year)');
    }
}
