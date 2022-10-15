<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221014200122 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Registration: add agree to terms date';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD agreed_terms_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('UPDATE user SET agreed_terms_at = NOW()');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP agreed_terms_at');
    }
}
