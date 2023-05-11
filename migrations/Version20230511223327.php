<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230511223327 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'make question and export tables time aware';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE export ADD created_at DATETIME NOT NULL DEFAULT NOW(), ADD updated_at DATETIME NOT NULL  DEFAULT NOW()');
        $this->addSql('ALTER TABLE question ADD created_at DATETIME NOT NULL DEFAULT NOW(), ADD updated_at DATETIME NOT NULL DEFAULT NOW()');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE export DROP created_at, DROP updated_at');
        $this->addSql('ALTER TABLE question DROP created_at, DROP updated_at');
    }
}
