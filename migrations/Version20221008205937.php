<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221008205937 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add a relation between a question and a spell';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE question ADD spell_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494E479EC90D FOREIGN KEY (spell_id) REFERENCES spell (id)');
        $this->addSql('CREATE INDEX IDX_B6F7494E479EC90D ON question (spell_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494E479EC90D');
        $this->addSql('DROP INDEX IDX_B6F7494E479EC90D ON question');
        $this->addSql('ALTER TABLE question DROP spell_id');
    }
}
