<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260428131500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace plaintext token column with split identifier + hashed_secret';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_7BA2F5EB5F37A13B ON api_token');
        $this->addSql('ALTER TABLE api_token ADD identifier VARCHAR(16) NOT NULL, ADD hashed_secret VARCHAR(64) NOT NULL, ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD last_used_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP expires_at, DROP token');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7BA2F5EB772E836A ON api_token (identifier)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_7BA2F5EB772E836A ON api_token');
        $this->addSql('ALTER TABLE api_token ADD expires_at DATETIME NOT NULL, ADD token VARCHAR(191) NOT NULL, DROP identifier, DROP hashed_secret, DROP created_at, DROP last_used_at');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7BA2F5EB5F37A13B ON api_token (token)');
    }
}
