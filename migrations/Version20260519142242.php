<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260519142242 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__api_token AS SELECT id, user_id, token, created_at, expires_at FROM api_token');
        $this->addSql('DROP TABLE api_token');
        $this->addSql('CREATE TABLE api_token (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, token VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, CONSTRAINT FK_API_TOKEN_USER FOREIGN KEY (user_id) REFERENCES app_user (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO api_token (id, user_id, token, created_at, expires_at) SELECT id, user_id, token, created_at, expires_at FROM __temp__api_token');
        $this->addSql('DROP TABLE __temp__api_token');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7BA2F5EB5F37A13B ON api_token (token)');
        $this->addSql('CREATE INDEX IDX_7BA2F5EBA76ED395 ON api_token (user_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__health_metric AS SELECT id, user_id, date, metric_key, metric_value FROM health_metric');
        $this->addSql('DROP TABLE health_metric');
        $this->addSql('CREATE TABLE health_metric (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, date DATE NOT NULL, metric_key VARCHAR(100) NOT NULL, metric_value DOUBLE PRECISION NOT NULL, CONSTRAINT FK_HM_USER FOREIGN KEY (user_id) REFERENCES app_user (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO health_metric (id, user_id, date, metric_key, metric_value) SELECT id, user_id, date, metric_key, metric_value FROM __temp__health_metric');
        $this->addSql('DROP TABLE __temp__health_metric');
        $this->addSql('CREATE UNIQUE INDEX uq_health_metric ON health_metric (user_id, date, metric_key)');
        $this->addSql('CREATE INDEX IDX_FC3CC666A76ED395 ON health_metric (user_id)');
        $this->addSql('CREATE INDEX idx_health_metric_user_date ON health_metric (user_id, date)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__workout_session AS SELECT id, date, type, duration_minutes, notes, created_at, user_id FROM workout_session');
        $this->addSql('DROP TABLE workout_session');
        $this->addSql('CREATE TABLE workout_session (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, date DATE NOT NULL, type VARCHAR(20) NOT NULL, duration_minutes INTEGER DEFAULT NULL, notes CLOB DEFAULT NULL, created_at DATETIME NOT NULL, user_id INTEGER DEFAULT NULL, CONSTRAINT FK_AC82B97CA76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON UPDATE NO ACTION ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO workout_session (id, date, type, duration_minutes, notes, created_at, user_id) SELECT id, date, type, duration_minutes, notes, created_at, user_id FROM __temp__workout_session');
        $this->addSql('DROP TABLE __temp__workout_session');
        $this->addSql('CREATE INDEX IDX_AC82B97CA76ED395 ON workout_session (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__api_token AS SELECT id, token, created_at, expires_at, user_id FROM api_token');
        $this->addSql('DROP TABLE api_token');
        $this->addSql('CREATE TABLE api_token (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, token VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL, expires_at DATETIME DEFAULT \'datetime(\'\'now\'\', \'\'+90 days\'\')\' NOT NULL, user_id INTEGER NOT NULL, CONSTRAINT FK_7BA2F5EBA76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO api_token (id, token, created_at, expires_at, user_id) SELECT id, token, created_at, expires_at, user_id FROM __temp__api_token');
        $this->addSql('DROP TABLE __temp__api_token');
        $this->addSql('CREATE INDEX IDX_API_TOKEN_USER ON api_token (user_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_API_TOKEN ON api_token (token)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__health_metric AS SELECT id, date, metric_key, metric_value, user_id FROM health_metric');
        $this->addSql('DROP TABLE health_metric');
        $this->addSql('CREATE TABLE health_metric (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, date DATE NOT NULL, metric_key VARCHAR(100) NOT NULL, metric_value DOUBLE PRECISION NOT NULL, user_id INTEGER NOT NULL, CONSTRAINT FK_FC3CC666A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO health_metric (id, date, metric_key, metric_value, user_id) SELECT id, date, metric_key, metric_value, user_id FROM __temp__health_metric');
        $this->addSql('DROP TABLE __temp__health_metric');
        $this->addSql('CREATE INDEX IDX_FC3CC666A76ED395 ON health_metric (user_id)');
        $this->addSql('CREATE UNIQUE INDEX uq_health_metric ON health_metric (user_id, date, metric_key)');
        $this->addSql('CREATE INDEX idx_hm_user_date ON health_metric (user_id, date)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__workout_session AS SELECT id, date, type, duration_minutes, notes, created_at, user_id FROM workout_session');
        $this->addSql('DROP TABLE workout_session');
        $this->addSql('CREATE TABLE workout_session (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, date DATE NOT NULL, type VARCHAR(20) NOT NULL, duration_minutes INTEGER DEFAULT NULL, notes CLOB DEFAULT NULL, created_at DATETIME NOT NULL, user_id INTEGER DEFAULT NULL, CONSTRAINT FK_AC82B97CA76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO workout_session (id, date, type, duration_minutes, notes, created_at, user_id) SELECT id, date, type, duration_minutes, notes, created_at, user_id FROM __temp__workout_session');
        $this->addSql('DROP TABLE __temp__workout_session');
        $this->addSql('CREATE INDEX IDX_AC82B97CA76ED395 ON workout_session (user_id)');
        $this->addSql('CREATE INDEX idx_ws_user_type_date ON workout_session (user_id, type, date)');
        $this->addSql('CREATE INDEX idx_ws_user_date ON workout_session (user_id, date)');
    }
}
