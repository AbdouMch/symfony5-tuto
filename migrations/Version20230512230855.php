<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\ExportStatus;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230512230855 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add export status table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE export_status (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, constant_code VARCHAR(100) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE export ADD export_status_id INT DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE export ADD CONSTRAINT FK_428C1694E6F1460D FOREIGN KEY (export_status_id) REFERENCES export_status (id)');
        $this->addSql('CREATE INDEX IDX_428C1694E6F1460D ON export (export_status_id)');
        $this->addSql('ALTER TABLE question CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');

        // add export status
        $exportStatus = [
          ExportStatus::PENDING,
          ExportStatus::IN_PROGRESS,
          ExportStatus::COMPLETED,
          ExportStatus::FAILED,
        ];

        foreach ($exportStatus as $status) {
            $this->addSql("INSERT INTO export_status (constant_code, name) VALUES ('{$status}', 'export.status.{$status}')");
        }

        $this->addSql("UPDATE export SET export_status_id=(
                            CASE WHEN status='pending' THEN 1
                            WHEN status='in_progress' THEN 2
                            WHEN status='complete' THEN 3
                            WHEN status='failed' THEN 4
                            ELSE status
                            END)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE export DROP FOREIGN KEY FK_428C1694E6F1460D');
        $this->addSql('DROP TABLE export_status');
        $this->addSql('DROP INDEX IDX_428C1694E6F1460D ON export');
        $this->addSql('ALTER TABLE export DROP export_status_id, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE question CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
    }
}
