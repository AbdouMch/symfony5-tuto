<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230211135443 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add questions to users relation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE questions_to_users (question_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_6DCB767C1E27F6BF (question_id), INDEX IDX_6DCB767CA76ED395 (user_id), PRIMARY KEY(question_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE questions_to_users ADD CONSTRAINT FK_6DCB767C1E27F6BF FOREIGN KEY (question_id) REFERENCES question (id)');
        $this->addSql('ALTER TABLE questions_to_users ADD CONSTRAINT FK_6DCB767CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494E29F6EE60');
        $this->addSql('DROP INDEX IDX_B6F7494E29F6EE60 ON question');
        $this->addSql('ALTER TABLE question DROP to_user_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE questions_to_users DROP FOREIGN KEY FK_6DCB767C1E27F6BF');
        $this->addSql('ALTER TABLE questions_to_users DROP FOREIGN KEY FK_6DCB767CA76ED395');
        $this->addSql('DROP TABLE questions_to_users');
        $this->addSql('ALTER TABLE question ADD to_user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494E29F6EE60 FOREIGN KEY (to_user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_B6F7494E29F6EE60 ON question (to_user_id)');
    }
}
