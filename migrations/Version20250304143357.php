<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250304143357 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cheltuieli (id INT AUTO_INCREMENT NOT NULL, comanda_id INT DEFAULT NULL, comunitar_id INT DEFAULT NULL, categorie_id INT NOT NULL, subcategorie_id INT DEFAULT NULL, consumabil_id INT DEFAULT NULL, suma DOUBLE PRECISION NOT NULL, data_cheltuiala DATE NOT NULL, descriere VARCHAR(255) DEFAULT NULL, litri_motorina DOUBLE PRECISION DEFAULT NULL, tva DOUBLE PRECISION DEFAULT NULL, comision_tva DOUBLE PRECISION DEFAULT NULL, INDEX IDX_BE69108A787958A8 (comanda_id), INDEX IDX_BE69108ACE80CD93 (comunitar_id), INDEX IDX_BE69108ABCF5E72D (categorie_id), INDEX IDX_BE69108A7B1204D (subcategorie_id), INDEX IDX_BE69108A1CA21B9B (consumabil_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE cheltuieli ADD CONSTRAINT FK_BE69108A787958A8 FOREIGN KEY (comanda_id) REFERENCES comenzi (id)');
        $this->addSql('ALTER TABLE cheltuieli ADD CONSTRAINT FK_BE69108ACE80CD93 FOREIGN KEY (comunitar_id) REFERENCES comenzi_comunitare (id)');
        $this->addSql('ALTER TABLE cheltuieli ADD CONSTRAINT FK_BE69108ABCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorii_cheltuieli (id)');
        $this->addSql('ALTER TABLE cheltuieli ADD CONSTRAINT FK_BE69108A7B1204D FOREIGN KEY (subcategorie_id) REFERENCES subcategorii_cheltuieli (id)');
        $this->addSql('ALTER TABLE cheltuieli ADD CONSTRAINT FK_BE69108A1CA21B9B FOREIGN KEY (consumabil_id) REFERENCES consumabile (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cheltuieli DROP FOREIGN KEY FK_BE69108A787958A8');
        $this->addSql('ALTER TABLE cheltuieli DROP FOREIGN KEY FK_BE69108ACE80CD93');
        $this->addSql('ALTER TABLE cheltuieli DROP FOREIGN KEY FK_BE69108ABCF5E72D');
        $this->addSql('ALTER TABLE cheltuieli DROP FOREIGN KEY FK_BE69108A7B1204D');
        $this->addSql('ALTER TABLE cheltuieli DROP FOREIGN KEY FK_BE69108A1CA21B9B');
        $this->addSql('DROP TABLE cheltuieli');
    }
}
