<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260507000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add weight_kg and calories_kcal columns to health_data';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE health_data ADD COLUMN weight_kg DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE health_data ADD COLUMN calories_kcal INTEGER DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__health_data AS SELECT id, user_id, date, steps, sleep_minutes, active_minutes FROM health_data');
        $this->addSql('DROP TABLE health_data');
        $this->addSql('CREATE TABLE health_data (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, date DATE NOT NULL, steps INTEGER DEFAULT NULL, sleep_minutes INTEGER DEFAULT NULL, active_minutes INTEGER DEFAULT NULL, CONSTRAINT FK_HEALTH_USER FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE)');
        $this->addSql('INSERT INTO health_data (id, user_id, date, steps, sleep_minutes, active_minutes) SELECT id, user_id, date, steps, sleep_minutes, active_minutes FROM __temp__health_data');
        $this->addSql('DROP TABLE __temp__health_data');
        $this->addSql('CREATE UNIQUE INDEX uq_health_user_date ON health_data (user_id, date)');
    }
}
