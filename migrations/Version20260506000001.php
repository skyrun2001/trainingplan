<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add api_token and health_data tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE api_token (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            user_id INTEGER NOT NULL,
            token VARCHAR(64) NOT NULL,
            created_at DATETIME NOT NULL,
            CONSTRAINT FK_API_TOKEN_USER FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_API_TOKEN ON api_token (token)');
        $this->addSql('CREATE INDEX IDX_API_TOKEN_USER ON api_token (user_id)');

        $this->addSql('CREATE TABLE health_data (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            user_id INTEGER NOT NULL,
            date DATE NOT NULL,
            steps INTEGER,
            sleep_minutes INTEGER,
            active_minutes INTEGER,
            CONSTRAINT FK_HEALTH_DATA_USER FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_HEALTH_USER_DATE ON health_data (user_id, date)');
        $this->addSql('CREATE INDEX IDX_HEALTH_DATA_USER ON health_data (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE api_token');
        $this->addSql('DROP TABLE health_data');
    }
}
