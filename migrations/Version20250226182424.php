<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250226182424 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE comenzi (id INT AUTO_INCREMENT NOT NULL, parc_auto_id INT NOT NULL, sofer VARCHAR(100) NOT NULL, data_start DATE NOT NULL, data_stop DATE NOT NULL, numar_km DOUBLE PRECISION NOT NULL, profit DOUBLE PRECISION DEFAULT NULL, INDEX IDX_103D2EEE55A86196 (parc_auto_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE comenzi ADD CONSTRAINT FK_103D2EEE55A86196 FOREIGN KEY (parc_auto_id) REFERENCES parc_auto (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comenzi DROP FOREIGN KEY FK_103D2EEE55A86196');
        $this->addSql('DROP TABLE comenzi');
    }
}
