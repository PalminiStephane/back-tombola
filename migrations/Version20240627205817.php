<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240627205817 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_6D28840DA76ED395 ON payment (user_id)');
        $this->addSql('ALTER TABLE result ADD draw_id INT NOT NULL, ADD winner_ticket_id INT NOT NULL');
        $this->addSql('ALTER TABLE result ADD CONSTRAINT FK_136AC1136FC5C1B8 FOREIGN KEY (draw_id) REFERENCES draw (id)');
        $this->addSql('ALTER TABLE result ADD CONSTRAINT FK_136AC11381922061 FOREIGN KEY (winner_ticket_id) REFERENCES ticket (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_136AC1136FC5C1B8 ON result (draw_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_136AC11381922061 ON result (winner_ticket_id)');
        $this->addSql('ALTER TABLE ticket ADD user_id INT NOT NULL, ADD draw_id INT NOT NULL');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA36FC5C1B8 FOREIGN KEY (draw_id) REFERENCES draw (id)');
        $this->addSql('CREATE INDEX IDX_97A0ADA3A76ED395 ON ticket (user_id)');
        $this->addSql('CREATE INDEX IDX_97A0ADA36FC5C1B8 ON ticket (draw_id)');
        $this->addSql('ALTER TABLE user ADD username VARCHAR(64) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE result DROP FOREIGN KEY FK_136AC1136FC5C1B8');
        $this->addSql('ALTER TABLE result DROP FOREIGN KEY FK_136AC11381922061');
        $this->addSql('DROP INDEX UNIQ_136AC1136FC5C1B8 ON result');
        $this->addSql('DROP INDEX UNIQ_136AC11381922061 ON result');
        $this->addSql('ALTER TABLE result DROP draw_id, DROP winner_ticket_id');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA3A76ED395');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA36FC5C1B8');
        $this->addSql('DROP INDEX IDX_97A0ADA3A76ED395 ON ticket');
        $this->addSql('DROP INDEX IDX_97A0ADA36FC5C1B8 ON ticket');
        $this->addSql('ALTER TABLE ticket DROP user_id, DROP draw_id');
        $this->addSql('ALTER TABLE user DROP username');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840DA76ED395');
        $this->addSql('DROP INDEX IDX_6D28840DA76ED395 ON payment');
        $this->addSql('ALTER TABLE payment DROP user_id');
    }
}
