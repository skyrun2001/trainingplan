<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260517102659 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add composite indexes for common query patterns on workout_session';
    }

    public function up(Schema $schema): void
    {
        // Covers findByDateRangeAndUser: WHERE user_id = ? AND date BETWEEN ? AND ?
        $this->addSql('CREATE INDEX idx_ws_user_date ON workout_session (user_id, date)');

        // Covers findRecentByTypeForUser: WHERE user_id = ? AND type = ? AND date >= ?
        $this->addSql('CREATE INDEX idx_ws_user_type_date ON workout_session (user_id, type, date)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_ws_user_date');
        $this->addSql('DROP INDEX IF EXISTS idx_ws_user_type_date');
    }
}
