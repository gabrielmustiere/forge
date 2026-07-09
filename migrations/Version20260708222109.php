<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute l'état de clone local sur `project` (story 008) : statut + chemin + horodatage + erreur.
 */
final class Version20260708222109 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Ajoute les champs d'état de clone (clone_status, cloned_at, local_path, last_clone_error) sur project.";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project ADD COLUMN clone_status VARCHAR(255) DEFAULT \'not_cloned\' NOT NULL');
        $this->addSql('ALTER TABLE project ADD COLUMN cloned_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE project ADD COLUMN local_path VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE project ADD COLUMN last_clone_error CLOB DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__project AS SELECT id, created_at, verification_status, verified_at, provider, url, name, token FROM project');
        $this->addSql('DROP TABLE project');
        $this->addSql('CREATE TABLE project (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, created_at DATETIME NOT NULL, verification_status VARCHAR(255) DEFAULT \'unverified\' NOT NULL, verified_at DATETIME DEFAULT NULL, provider VARCHAR(255) NOT NULL, url VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, token CLOB NOT NULL)');
        $this->addSql('INSERT INTO project (id, created_at, verification_status, verified_at, provider, url, name, token) SELECT id, created_at, verification_status, verified_at, provider, url, name, token FROM __temp__project');
        $this->addSql('DROP TABLE __temp__project');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_PROJECT_URL ON project (url)');
    }
}
