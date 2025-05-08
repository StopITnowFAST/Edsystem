<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250508103840 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE subject_wiki (id INT AUTO_INCREMENT NOT NULL, text LONGTEXT DEFAULT NULL, subject_id INT DEFAULT NULL, cat_upload_file TINYINT(1) NOT NULL, created_at INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE subject_wiki_file (id INT AUTO_INCREMENT NOT NULL, wiki_id INT NOT NULL, file_type VARCHAR(20) NOT NULL, file_id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE subject_wiki');
        $this->addSql('DROP TABLE subject_wiki_file');
    }
}
