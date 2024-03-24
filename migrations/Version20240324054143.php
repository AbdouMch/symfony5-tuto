<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240324054143 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add version to question table for optimistic lock';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE question ADD version SMALLINT UNSIGNED DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE question DROP version');
    }
}
