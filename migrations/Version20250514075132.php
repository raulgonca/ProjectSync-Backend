<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250514075132 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE repo_colaboradores (repo_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_53B2DC1ABD359B2D (repo_id), INDEX IDX_53B2DC1AA76ED395 (user_id), PRIMARY KEY(repo_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE repo_colaboradores ADD CONSTRAINT FK_53B2DC1ABD359B2D FOREIGN KEY (repo_id) REFERENCES repo (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE repo_colaboradores ADD CONSTRAINT FK_53B2DC1AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE repo CHANGE file file VARCHAR(255) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE repo_colaboradores DROP FOREIGN KEY FK_53B2DC1ABD359B2D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE repo_colaboradores DROP FOREIGN KEY FK_53B2DC1AA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE repo_colaboradores
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE repo CHANGE file file LONGBLOB DEFAULT NULL
        SQL);
    }
}
