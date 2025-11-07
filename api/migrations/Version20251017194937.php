<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251017194937 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE collaboration (id INT AUTO_INCREMENT NOT NULL, playlist_id INT DEFAULT NULL, uuid LONGTEXT NOT NULL, title VARCHAR(255) NOT NULL, add_limit INT DEFAULT NULL, email VARCHAR(255) NOT NULL, added_song_number INT DEFAULT NULL, INDEX IDX_DA3AE3236BBD148 (playlist_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE collaboration ADD CONSTRAINT FK_DA3AE3236BBD148 FOREIGN KEY (playlist_id) REFERENCES playlist (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE collaboration_invitation DROP FOREIGN KEY FK_7D3FEEE66BBD148
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE collaboration_invitation
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE collaboration_invitation (id INT AUTO_INCREMENT NOT NULL, playlist_id INT DEFAULT NULL, uuid LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, add_limit INT DEFAULT NULL, email VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, added_song_number INT DEFAULT NULL, INDEX IDX_7D3FEEE66BBD148 (playlist_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE collaboration_invitation ADD CONSTRAINT FK_7D3FEEE66BBD148 FOREIGN KEY (playlist_id) REFERENCES playlist (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE collaboration DROP FOREIGN KEY FK_DA3AE3236BBD148
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE collaboration
        SQL);
    }
}
