<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250509191908 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE student ADD student_token VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE subject_wiki CHANGE cat_upload_file can_upload_file TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE teacher ADD teacher_token VARCHAR(50) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE student DROP student_token');
        $this->addSql('ALTER TABLE subject_wiki CHANGE can_upload_file cat_upload_file TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE teacher DROP teacher_token');
    }
}
