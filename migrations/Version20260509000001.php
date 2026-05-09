<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260509000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace specific health_data table with generic health_metric key-value table';
    }

    public function up(Schema $schema): void
    {
        // Drop the old fixed-column health table (steps, sleep, weight, calories...)
        $this->addSql('DROP TABLE IF EXISTS health_data');

        // Generic: one row per (user, date, metric_key) → any metric, forever extensible
        $this->addSql('CREATE TABLE health_metric (
            id           INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            user_id      INTEGER NOT NULL,
            date         DATE NOT NULL,
            metric_key   VARCHAR(100) NOT NULL,
            metric_value DOUBLE PRECISION NOT NULL,
            CONSTRAINT FK_HM_USER FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE
        )');
        $this->addSql('CREATE UNIQUE INDEX uq_health_metric    ON health_metric (user_id, date, metric_key)');
        $this->addSql('CREATE        INDEX idx_hm_user_date    ON health_metric (user_id, date)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS health_metric');
        $this->addSql('CREATE TABLE health_data (
            id             INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            user_id        INTEGER NOT NULL,
            date           DATE NOT NULL,
            steps          INTEGER DEFAULT NULL,
            sleep_minutes  INTEGER DEFAULT NULL,
            active_minutes INTEGER DEFAULT NULL,
            weight_kg      DOUBLE PRECISION DEFAULT NULL,
            calories_kcal  INTEGER DEFAULT NULL,
            CONSTRAINT FK_HEALTH_USER FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE
        )');
        $this->addSql('CREATE UNIQUE INDEX uq_health_user_date ON health_data (user_id, date)');
    }
}
