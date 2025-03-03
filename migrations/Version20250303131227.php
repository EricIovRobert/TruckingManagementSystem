<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250303131227 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE categorii_cheltuieli (id INT AUTO_INCREMENT NOT NULL, nume VARCHAR(100) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE consumabile (id INT AUTO_INCREMENT NOT NULL, categorie_id INT NOT NULL, nume VARCHAR(100) NOT NULL, pret_maxim DOUBLE PRECISION NOT NULL, km_utilizare_max DOUBLE PRECISION DEFAULT NULL, INDEX IDX_B8F211C3BCF5E72D (categorie_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE subcategorii_cheltuieli (id INT AUTO_INCREMENT NOT NULL, categorie_id INT NOT NULL, nume VARCHAR(100) NOT NULL, pret_standard DOUBLE PRECISION DEFAULT NULL, pret_per_l DOUBLE PRECISION DEFAULT NULL, INDEX IDX_F25E766BCF5E72D (categorie_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE consumabile ADD CONSTRAINT FK_B8F211C3BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorii_cheltuieli (id)');
        $this->addSql('ALTER TABLE subcategorii_cheltuieli ADD CONSTRAINT FK_F25E766BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorii_cheltuieli (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE consumabile DROP FOREIGN KEY FK_B8F211C3BCF5E72D');
        $this->addSql('ALTER TABLE subcategorii_cheltuieli DROP FOREIGN KEY FK_F25E766BCF5E72D');
        $this->addSql('DROP TABLE categorii_cheltuieli');
        $this->addSql('DROP TABLE consumabile');
        $this->addSql('DROP TABLE subcategorii_cheltuieli');
    }
}
