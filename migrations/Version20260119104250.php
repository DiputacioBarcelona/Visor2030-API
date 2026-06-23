<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260119104250 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE territorial_region (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE municipality ADD territorial_region_id INT DEFAULT NULL, ADD is_industrial TINYINT(1) DEFAULT NULL, ADD is_in_amb TINYINT(1) DEFAULT NULL, ADD is_in_rmb TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE municipality ADD CONSTRAINT FK_C6F56628EDA42230 FOREIGN KEY (territorial_region_id) REFERENCES territorial_region (id)');
        $this->addSql('CREATE INDEX IDX_C6F56628EDA42230 ON municipality (territorial_region_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE municipality DROP FOREIGN KEY FK_C6F56628EDA42230');
        $this->addSql('DROP TABLE territorial_region');
        $this->addSql('DROP INDEX IDX_C6F56628EDA42230 ON municipality');
        $this->addSql('ALTER TABLE municipality DROP territorial_region_id, DROP is_industrial, DROP is_in_amb, DROP is_in_rmb');
    }
}
