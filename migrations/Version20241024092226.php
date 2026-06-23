<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241024092226 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE comarca (id INT AUTO_INCREMENT NOT NULL, province_id INT DEFAULT NULL, comarca_name VARCHAR(255) NOT NULL, comarca_code VARCHAR(7) NOT NULL, UNIQUE INDEX UNIQ_1FB8C287F3A5ABBA (comarca_code), INDEX IDX_1FB8C287E946114A (province_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comarca_value (id INT AUTO_INCREMENT NOT NULL, comarca_id INT NOT NULL, indicator_id INT DEFAULT NULL, value DOUBLE PRECISION NOT NULL, year SMALLINT DEFAULT NULL, month SMALLINT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_C893A94BBE4D4658 (comarca_id), INDEX IDX_C893A94B4402854A (indicator_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE indicator (id INT AUTO_INCREMENT NOT NULL, target_id INT NOT NULL, sdg SMALLINT DEFAULT NULL, indicator_id VARCHAR(7) DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, sign TINYINT(1) NOT NULL, unit VARCHAR(15) DEFAULT NULL, scale SMALLINT DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, source VARCHAR(255) DEFAULT NULL, api_url_municipalities VARCHAR(255) DEFAULT NULL, INDEX IDX_D1349DB3158E0B66 (target_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE municipality (id INT AUTO_INCREMENT NOT NULL, comarca_id INT DEFAULT NULL, municipality_name VARCHAR(255) NOT NULL, municipality_code VARCHAR(7) NOT NULL, municipality_code_6 VARCHAR(7) NOT NULL, loc VARCHAR(63) NOT NULL, UNIQUE INDEX UNIQ_C6F56628EB634D6A (municipality_code), UNIQUE INDEX UNIQ_C6F56628243F939E (municipality_code_6), INDEX idx_comarca (comarca_id), INDEX idx_code (municipality_code), INDEX idx_code_6 (municipality_code_6), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE municipality_value (id INT AUTO_INCREMENT NOT NULL, municipality_id INT NOT NULL, indicator_id INT DEFAULT NULL, sdg SMALLINT DEFAULT NULL, value DOUBLE PRECISION NOT NULL, value2 DOUBLE PRECISION DEFAULT NULL, year SMALLINT DEFAULT NULL, month SMALLINT DEFAULT NULL, unit VARCHAR(15) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_4E58BD79AE6F181C (municipality_id), INDEX IDX_4E58BD794402854A (indicator_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE population (id INT AUTO_INCREMENT NOT NULL, municipality_id INT NOT NULL, population_count INT NOT NULL, year SMALLINT DEFAULT NULL, INDEX IDX_B449A008AE6F181C (municipality_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE province (id INT AUTO_INCREMENT NOT NULL, province_code VARCHAR(7) NOT NULL, province_name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_4ADAD40B7E5E4835 (province_code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE province_value (id INT AUTO_INCREMENT NOT NULL, province_id INT NOT NULL, indicator_id INT NOT NULL, value DOUBLE PRECISION NOT NULL, year SMALLINT DEFAULT NULL, month SMALLINT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_B519CC19E946114A (province_id), INDEX IDX_B519CC194402854A (indicator_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE target (id INT AUTO_INCREMENT NOT NULL, sdg SMALLINT NOT NULL, target_id VARCHAR(7) DEFAULT NULL, target_name VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_466F2FFC158E0B66 (target_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE comarca ADD CONSTRAINT FK_1FB8C287E946114A FOREIGN KEY (province_id) REFERENCES province (id)');
        $this->addSql('ALTER TABLE comarca_value ADD CONSTRAINT FK_C893A94BBE4D4658 FOREIGN KEY (comarca_id) REFERENCES comarca (id)');
        $this->addSql('ALTER TABLE comarca_value ADD CONSTRAINT FK_C893A94B4402854A FOREIGN KEY (indicator_id) REFERENCES indicator (id)');
        $this->addSql('ALTER TABLE indicator ADD CONSTRAINT FK_D1349DB3158E0B66 FOREIGN KEY (target_id) REFERENCES target (id)');
        $this->addSql('ALTER TABLE municipality ADD CONSTRAINT FK_C6F56628BE4D4658 FOREIGN KEY (comarca_id) REFERENCES comarca (id)');
        $this->addSql('ALTER TABLE municipality_value ADD CONSTRAINT FK_4E58BD79AE6F181C FOREIGN KEY (municipality_id) REFERENCES municipality (id)');
        $this->addSql('ALTER TABLE municipality_value ADD CONSTRAINT FK_4E58BD794402854A FOREIGN KEY (indicator_id) REFERENCES indicator (id)');
        $this->addSql('ALTER TABLE population ADD CONSTRAINT FK_B449A008AE6F181C FOREIGN KEY (municipality_id) REFERENCES municipality (id)');
        $this->addSql('ALTER TABLE province_value ADD CONSTRAINT FK_B519CC19E946114A FOREIGN KEY (province_id) REFERENCES province (id)');
        $this->addSql('ALTER TABLE province_value ADD CONSTRAINT FK_B519CC194402854A FOREIGN KEY (indicator_id) REFERENCES indicator (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comarca DROP FOREIGN KEY FK_1FB8C287E946114A');
        $this->addSql('ALTER TABLE comarca_value DROP FOREIGN KEY FK_C893A94BBE4D4658');
        $this->addSql('ALTER TABLE comarca_value DROP FOREIGN KEY FK_C893A94B4402854A');
        $this->addSql('ALTER TABLE indicator DROP FOREIGN KEY FK_D1349DB3158E0B66');
        $this->addSql('ALTER TABLE municipality DROP FOREIGN KEY FK_C6F56628BE4D4658');
        $this->addSql('ALTER TABLE municipality_value DROP FOREIGN KEY FK_4E58BD79AE6F181C');
        $this->addSql('ALTER TABLE municipality_value DROP FOREIGN KEY FK_4E58BD794402854A');
        $this->addSql('ALTER TABLE population DROP FOREIGN KEY FK_B449A008AE6F181C');
        $this->addSql('ALTER TABLE province_value DROP FOREIGN KEY FK_B519CC19E946114A');
        $this->addSql('ALTER TABLE province_value DROP FOREIGN KEY FK_B519CC194402854A');
        $this->addSql('DROP TABLE comarca');
        $this->addSql('DROP TABLE comarca_value');
        $this->addSql('DROP TABLE indicator');
        $this->addSql('DROP TABLE municipality');
        $this->addSql('DROP TABLE municipality_value');
        $this->addSql('DROP TABLE population');
        $this->addSql('DROP TABLE province');
        $this->addSql('DROP TABLE province_value');
        $this->addSql('DROP TABLE target');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
