<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250324081356 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE casa_expeditii (id INT AUTO_INCREMENT NOT NULL, nume_client VARCHAR(255) NOT NULL, nr_comanda_client VARCHAR(255) NOT NULL, pret_client DOUBLE PRECISION NOT NULL, nr_factura_client VARCHAR(255) NOT NULL, nume_transportator VARCHAR(255) NOT NULL, pret_transportator DOUBLE PRECISION NOT NULL, nr_comanda_transportator VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE casa_expeditii');
    }
}
