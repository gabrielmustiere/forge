<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260704214937 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le statut de vérification (défaut unverified) et son horodatage sur project.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project ADD COLUMN verification_status VARCHAR(255) DEFAULT \'unverified\' NOT NULL');
        $this->addSql('ALTER TABLE project ADD COLUMN verified_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__project AS SELECT id, created_at, provider, url, name, token FROM project');
        $this->addSql('DROP TABLE project');
        $this->addSql('CREATE TABLE project (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, created_at DATETIME NOT NULL, provider VARCHAR(255) NOT NULL, url VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, token CLOB NOT NULL)');
        $this->addSql('INSERT INTO project (id, created_at, provider, url, name, token) SELECT id, created_at, provider, url, name, token FROM __temp__project');
        $this->addSql('DROP TABLE __temp__project');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_PROJECT_URL ON project (url)');
    }
}
