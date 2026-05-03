<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260503115826 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE exercise_log (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, exercise_name VARCHAR(120) NOT NULL, set_number INTEGER NOT NULL, reps INTEGER DEFAULT NULL, weight_kg NUMERIC(6, 2) DEFAULT NULL, rpe INTEGER DEFAULT NULL, session_id INTEGER NOT NULL, CONSTRAINT FK_1960CDB9613FECDF FOREIGN KEY (session_id) REFERENCES workout_session (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_1960CDB9613FECDF ON exercise_log (session_id)');
        $this->addSql('CREATE TABLE workout_session (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, date DATE NOT NULL, type VARCHAR(20) NOT NULL, duration_minutes INTEGER DEFAULT NULL, notes CLOB DEFAULT NULL, created_at DATETIME NOT NULL)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE exercise_log');
        $this->addSql('DROP TABLE workout_session');
    }
}
