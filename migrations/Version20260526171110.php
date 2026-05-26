<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260526171110 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE api_tokens (id INT AUTO_INCREMENT NOT NULL, token_hash VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL, revoked_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX idx_api_tokens_user_id (user_id), UNIQUE INDEX uniq_api_tokens_token_hash (token_hash), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, login VARCHAR(8) NOT NULL, phone VARCHAR(8) NOT NULL, pass VARCHAR(64) NOT NULL, roles JSON NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX idx_users_login (login), UNIQUE INDEX uniq_users_login_pass (login, pass), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE api_tokens ADD CONSTRAINT FK_2CAD560EA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE api_tokens DROP FOREIGN KEY FK_2CAD560EA76ED395');
        $this->addSql('DROP TABLE api_tokens');
        $this->addSql('DROP TABLE users');
    }
}
