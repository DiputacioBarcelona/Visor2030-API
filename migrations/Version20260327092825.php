<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260327092825 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE aggregation (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(100) NOT NULL, agg_group VARCHAR(50) NOT NULL, UNIQUE INDEX UNIQ_9C886393989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE municipality_aggregation (municipality_id INT NOT NULL, aggregation_id INT NOT NULL, INDEX IDX_59D11D0FAE6F181C (municipality_id), INDEX IDX_59D11D0F34A7F073 (aggregation_id), PRIMARY KEY(municipality_id, aggregation_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE municipality_aggregation ADD CONSTRAINT FK_59D11D0FAE6F181C FOREIGN KEY (municipality_id) REFERENCES municipality (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE municipality_aggregation ADD CONSTRAINT FK_59D11D0F34A7F073 FOREIGN KEY (aggregation_id) REFERENCES aggregation (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE municipality_aggregation DROP FOREIGN KEY FK_59D11D0FAE6F181C');
        $this->addSql('ALTER TABLE municipality_aggregation DROP FOREIGN KEY FK_59D11D0F34A7F073');
        $this->addSql('DROP TABLE aggregation');
        $this->addSql('DROP TABLE municipality_aggregation');
    }
}
