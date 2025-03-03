<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250303081355 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE comenzi_comunitare (id INT AUTO_INCREMENT NOT NULL, nr_auto_id INT NOT NULL, nr_auto_snapshot VARCHAR(20) NOT NULL, sofer VARCHAR(100) NOT NULL, data_start DATE NOT NULL, data_stop DATE DEFAULT NULL, nr_km DOUBLE PRECISION DEFAULT NULL, profit DOUBLE PRECISION DEFAULT NULL, kg DOUBLE PRECISION NOT NULL, pret DOUBLE PRECISION NOT NULL, INDEX IDX_6022EDEBFECE30D7 (nr_auto_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE comenzi_comunitare ADD CONSTRAINT FK_6022EDEBFECE30D7 FOREIGN KEY (nr_auto_id) REFERENCES parc_auto (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comenzi_comunitare DROP FOREIGN KEY FK_6022EDEBFECE30D7');
        $this->addSql('DROP TABLE comenzi_comunitare');
    }
}
