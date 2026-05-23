<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260523000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add schedule (JSON) column to supplement table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE supplement ADD COLUMN schedule CLOB NOT NULL DEFAULT '[]'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__supplement AS SELECT id, user_id, name, dosage, servings_per_day, servings_remaining, total_servings, unit, warning_days, notes, sort_order, created_at FROM supplement');
        $this->addSql('DROP TABLE supplement');
        $this->addSql('CREATE TABLE supplement (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, name VARCHAR(100) NOT NULL, dosage VARCHAR(50) DEFAULT NULL, servings_per_day INTEGER NOT NULL, servings_remaining NUMERIC(10, 2) NOT NULL, total_servings NUMERIC(10, 2) NOT NULL, unit VARCHAR(30) DEFAULT NULL, warning_days INTEGER NOT NULL, notes VARCHAR(255) DEFAULT NULL, sort_order INTEGER NOT NULL, created_at DATETIME NOT NULL, CONSTRAINT FK_15A73C9A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO supplement (id, user_id, name, dosage, servings_per_day, servings_remaining, total_servings, unit, warning_days, notes, sort_order, created_at) SELECT id, user_id, name, dosage, servings_per_day, servings_remaining, total_servings, unit, warning_days, notes, sort_order, created_at FROM __temp__supplement');
        $this->addSql('DROP TABLE __temp__supplement');
    }
}
