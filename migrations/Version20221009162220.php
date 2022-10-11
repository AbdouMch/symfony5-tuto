<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221009162220 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Spells could have an owner';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE spell ADD owner_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE spell ADD CONSTRAINT FK_D03FCD8D7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_D03FCD8D7E3C61F9 ON spell (owner_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE spell DROP FOREIGN KEY FK_D03FCD8D7E3C61F9');
        $this->addSql('DROP INDEX IDX_D03FCD8D7E3C61F9 ON spell');
        $this->addSql('ALTER TABLE spell DROP owner_id');
    }
}
