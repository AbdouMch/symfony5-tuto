<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221030085015 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'A user can ask a question for another user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE question ADD to_user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494E29F6EE60 FOREIGN KEY (to_user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_B6F7494E29F6EE60 ON question (to_user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494E29F6EE60');
        $this->addSql('DROP INDEX IDX_B6F7494E29F6EE60 ON question');
        $this->addSql('ALTER TABLE question DROP to_user_id');
    }
}
