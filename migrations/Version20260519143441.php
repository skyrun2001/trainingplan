<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260519143441 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE supplement (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, dosage VARCHAR(50) DEFAULT NULL, servings_per_day INTEGER NOT NULL, servings_remaining NUMERIC(10, 2) NOT NULL, total_servings NUMERIC(10, 2) NOT NULL, unit VARCHAR(30) DEFAULT NULL, warning_days INTEGER NOT NULL, notes VARCHAR(255) DEFAULT NULL, sort_order INTEGER NOT NULL, created_at DATETIME NOT NULL, user_id INTEGER NOT NULL, CONSTRAINT FK_15A73C9A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_15A73C9A76ED395 ON supplement (user_id)');
        $this->addSql('ALTER TABLE workout_session ADD COLUMN score INTEGER DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE supplement');
        $this->addSql('CREATE TEMPORARY TABLE __temp__workout_session AS SELECT id, date, type, duration_minutes, notes, created_at, user_id FROM workout_session');
        $this->addSql('DROP TABLE workout_session');
        $this->addSql('CREATE TABLE workout_session (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, date DATE NOT NULL, type VARCHAR(20) NOT NULL, duration_minutes INTEGER DEFAULT NULL, notes CLOB DEFAULT NULL, created_at DATETIME NOT NULL, user_id INTEGER DEFAULT NULL, CONSTRAINT FK_AC82B97CA76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO workout_session (id, date, type, duration_minutes, notes, created_at, user_id) SELECT id, date, type, duration_minutes, notes, created_at, user_id FROM __temp__workout_session');
        $this->addSql('DROP TABLE __temp__workout_session');
        $this->addSql('CREATE INDEX IDX_AC82B97CA76ED395 ON workout_session (user_id)');
    }
}
