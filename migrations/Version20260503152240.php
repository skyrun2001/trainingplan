<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260503152240 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE app_user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, username VARCHAR(80) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_88BDF3E9F85E0677 ON app_user (username)');
        $this->addSql('CREATE TABLE plan_day (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, type VARCHAR(30) NOT NULL, label VARCHAR(120) NOT NULL, color VARCHAR(120) NOT NULL, focus VARCHAR(120) DEFAULT NULL, note CLOB DEFAULT NULL, sort_order INTEGER NOT NULL, plan_id INTEGER NOT NULL, CONSTRAINT FK_E94192CDE899029B FOREIGN KEY (plan_id) REFERENCES training_plan (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_E94192CDE899029B ON plan_day (plan_id)');
        $this->addSql('CREATE TABLE plan_exercise (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, sort_order INTEGER NOT NULL, section VARCHAR(80) NOT NULL, name VARCHAR(120) NOT NULL, default_sets INTEGER NOT NULL, default_reps INTEGER DEFAULT NULL, progression_note VARCHAR(120) DEFAULT NULL, is_new BOOLEAN NOT NULL, day_id INTEGER NOT NULL, CONSTRAINT FK_A0BFE2669C24126 FOREIGN KEY (day_id) REFERENCES plan_day (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_A0BFE2669C24126 ON plan_exercise (day_id)');
        $this->addSql('CREATE TABLE training_plan (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(120) NOT NULL, is_active BOOLEAN NOT NULL, created_at DATETIME NOT NULL, user_id INTEGER NOT NULL, CONSTRAINT FK_D2C01C3EA76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_D2C01C3EA76ED395 ON training_plan (user_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__workout_session AS SELECT id, date, type, duration_minutes, notes, created_at FROM workout_session');
        $this->addSql('DROP TABLE workout_session');
        $this->addSql('CREATE TABLE workout_session (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, date DATE NOT NULL, type VARCHAR(20) NOT NULL, duration_minutes INTEGER DEFAULT NULL, notes CLOB DEFAULT NULL, created_at DATETIME NOT NULL, user_id INTEGER DEFAULT NULL, CONSTRAINT FK_AC82B97CA76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO workout_session (id, date, type, duration_minutes, notes, created_at) SELECT id, date, type, duration_minutes, notes, created_at FROM __temp__workout_session');
        $this->addSql('DROP TABLE __temp__workout_session');
        $this->addSql('CREATE INDEX IDX_AC82B97CA76ED395 ON workout_session (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE app_user');
        $this->addSql('DROP TABLE plan_day');
        $this->addSql('DROP TABLE plan_exercise');
        $this->addSql('DROP TABLE training_plan');
        $this->addSql('CREATE TEMPORARY TABLE __temp__workout_session AS SELECT id, date, type, duration_minutes, notes, created_at FROM workout_session');
        $this->addSql('DROP TABLE workout_session');
        $this->addSql('CREATE TABLE workout_session (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, date DATE NOT NULL, type VARCHAR(20) NOT NULL, duration_minutes INTEGER DEFAULT NULL, notes CLOB DEFAULT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO workout_session (id, date, type, duration_minutes, notes, created_at) SELECT id, date, type, duration_minutes, notes, created_at FROM __temp__workout_session');
        $this->addSql('DROP TABLE __temp__workout_session');
    }
}
