<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220930214656 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add 2FA authentication';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD totpSecret VARCHAR(255) DEFAULT NULL, ADD is_totp_enabled TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP totpSecret, DROP is_totp_enabled');
    }
}
