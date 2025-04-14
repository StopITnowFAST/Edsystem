<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250208120335 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE teacher ADD first_name VARCHAR(255) DEFAULT NULL, ADD middle_name VARCHAR(255) DEFAULT NULL, DROP name, DROP second_name');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE teacher ADD name VARCHAR(255) DEFAULT NULL, ADD second_name VARCHAR(255) DEFAULT NULL, DROP first_name, DROP middle_name');
    }
}
