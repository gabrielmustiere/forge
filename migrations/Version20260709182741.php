<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Crée les tables `interview` et `interview_message` du parcours de cadrage (story 009).
 *
 * Aucun backfill : nouvelles données. Les deux FK sont `ON DELETE CASCADE` (supprimer un
 * projet purge ses interviews et leurs messages) et indexées.
 */
final class Version20260709182741 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée les tables interview + interview_message (parcours de cadrage, story 009).';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE interview (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, status VARCHAR(255) DEFAULT \'awaiting\' NOT NULL, story_slug VARCHAR(255) DEFAULT NULL, pull_request_url VARCHAR(255) DEFAULT NULL, last_error CLOB DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, session_id VARCHAR(255) NOT NULL, project_id INTEGER NOT NULL, CONSTRAINT FK_CF1D3C34166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_CF1D3C34166D1F9C ON interview (project_id)');
        $this->addSql('CREATE TABLE interview_message (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, created_at DATETIME NOT NULL, role VARCHAR(255) NOT NULL, content CLOB NOT NULL, interview_id INTEGER NOT NULL, CONSTRAINT FK_78BC2DEC55D69D95 FOREIGN KEY (interview_id) REFERENCES interview (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_78BC2DEC55D69D95 ON interview_message (interview_id)');
    }

    public function down(Schema $schema): void
    {
        // Table enfant d'abord (FK vers interview), puis la table parente.
        $this->addSql('DROP TABLE interview_message');
        $this->addSql('DROP TABLE interview');
    }
}
