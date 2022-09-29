<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220929194423 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is blocked to user entity to prevent some users from accessing the site';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD is_blocked TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP is_blocked');
    }
}
