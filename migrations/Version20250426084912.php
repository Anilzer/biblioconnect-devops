<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250426084912 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE livre DROP availability
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE stock ADD livre_id INT DEFAULT NULL, DROP livre
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE stock ADD CONSTRAINT FK_4B36566037D925CB FOREIGN KEY (livre_id) REFERENCES livre (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_4B36566037D925CB ON stock (livre_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE livre ADD availability TINYINT(1) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE stock DROP FOREIGN KEY FK_4B36566037D925CB
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_4B36566037D925CB ON stock
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE stock ADD livre VARCHAR(255) NOT NULL, DROP livre_id
        SQL);
    }
}
