<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251209085523 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ruralitat (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ubicacio (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE municipality ADD ubicacio_id INT DEFAULT NULL, ADD ruralitat_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE municipality ADD CONSTRAINT FK_C6F566285282DF59 FOREIGN KEY (ubicacio_id) REFERENCES ubicacio (id)');
        $this->addSql('ALTER TABLE municipality ADD CONSTRAINT FK_C6F5662855A361F6 FOREIGN KEY (ruralitat_id) REFERENCES ruralitat (id)');
        $this->addSql('CREATE INDEX IDX_C6F566285282DF59 ON municipality (ubicacio_id)');
        $this->addSql('CREATE INDEX IDX_C6F5662855A361F6 ON municipality (ruralitat_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE municipality DROP FOREIGN KEY FK_C6F5662855A361F6');
        $this->addSql('ALTER TABLE municipality DROP FOREIGN KEY FK_C6F566285282DF59');
        $this->addSql('DROP TABLE ruralitat');
        $this->addSql('DROP TABLE ubicacio');
        $this->addSql('DROP INDEX IDX_C6F566285282DF59 ON municipality');
        $this->addSql('DROP INDEX IDX_C6F5662855A361F6 ON municipality');
        $this->addSql('ALTER TABLE municipality DROP ubicacio_id, DROP ruralitat_id');
    }
}
