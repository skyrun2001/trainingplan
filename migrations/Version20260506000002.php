<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add expires_at to api_token; expire existing tokens in 90 days';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE api_token ADD COLUMN expires_at DATETIME NOT NULL DEFAULT (datetime('now', '+90 days'))");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__api_token AS SELECT id, user_id, token, created_at FROM api_token');
        $this->addSql('DROP TABLE api_token');
        $this->addSql('CREATE TABLE api_token (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, token VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL, CONSTRAINT FK_API_TOKEN_USER FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE)');
        $this->addSql('INSERT INTO api_token (id, user_id, token, created_at) SELECT id, user_id, token, created_at FROM __temp__api_token');
        $this->addSql('DROP TABLE __temp__api_token');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_API_TOKEN ON api_token (token)');
    }
}
