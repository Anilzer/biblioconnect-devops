<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250426101841 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE livre DROP FOREIGN KEY FK_AC634F99DCD6110
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX FK_AC634F99DCD6110 ON livre
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE livre DROP stock_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE stock DROP INDEX IDX_4B36566037D925CB, ADD UNIQUE INDEX UNIQ_4B36566037D925CB (livre_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE stock CHANGE livre_id livre_id INT NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE livre ADD stock_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE livre ADD CONSTRAINT FK_AC634F99DCD6110 FOREIGN KEY (stock_id) REFERENCES stock (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX FK_AC634F99DCD6110 ON livre (stock_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE stock DROP INDEX UNIQ_4B36566037D925CB, ADD INDEX IDX_4B36566037D925CB (livre_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE stock CHANGE livre_id livre_id INT DEFAULT NULL
        SQL);
    }
}
