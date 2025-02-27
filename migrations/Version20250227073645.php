<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250227073645 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE retururi (id INT AUTO_INCREMENT NOT NULL, comanda_id INT NOT NULL, firma VARCHAR(100) NOT NULL, ruta_incarcare VARCHAR(255) NOT NULL, ruta_descarcare VARCHAR(255) NOT NULL, kg DOUBLE PRECISION NOT NULL, pret DOUBLE PRECISION NOT NULL, liber VARCHAR(255) DEFAULT NULL, facturat TINYINT(1) NOT NULL, INDEX IDX_49330A8A787958A8 (comanda_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tururi (id INT AUTO_INCREMENT NOT NULL, comanda_id INT NOT NULL, firma VARCHAR(100) NOT NULL, ruta_incarcare VARCHAR(255) NOT NULL, ruta_descarcare VARCHAR(255) NOT NULL, kg DOUBLE PRECISION NOT NULL, pret DOUBLE PRECISION NOT NULL, liber VARCHAR(255) DEFAULT NULL, facturat TINYINT(1) NOT NULL, INDEX IDX_AA2881EF787958A8 (comanda_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE retururi ADD CONSTRAINT FK_49330A8A787958A8 FOREIGN KEY (comanda_id) REFERENCES comenzi (id)');
        $this->addSql('ALTER TABLE tururi ADD CONSTRAINT FK_AA2881EF787958A8 FOREIGN KEY (comanda_id) REFERENCES comenzi (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE retururi DROP FOREIGN KEY FK_49330A8A787958A8');
        $this->addSql('ALTER TABLE tururi DROP FOREIGN KEY FK_AA2881EF787958A8');
        $this->addSql('DROP TABLE retururi');
        $this->addSql('DROP TABLE tururi');
    }
}
