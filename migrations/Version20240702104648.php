<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240702104648 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE draws (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(128) NOT NULL, description LONGTEXT DEFAULT NULL, draw_date DATE NOT NULL, ticket_price NUMERIC(10, 2) NOT NULL, tickets_available INT NOT NULL, total_tickets INT NOT NULL, status VARCHAR(64) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE notifications (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, notification_type VARCHAR(100) NOT NULL, notification_content LONGTEXT DEFAULT NULL, notification_date DATE NOT NULL, read_status TINYINT(1) NOT NULL, INDEX IDX_6000B0D3A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE paiements (id INT AUTO_INCREMENT NOT NULL, transaction_id INT NOT NULL, amount_paid NUMERIC(10, 2) NOT NULL, payment_date DATE NOT NULL, status VARCHAR(64) NOT NULL, UNIQUE INDEX UNIQ_E1B02E122FC0CB0F (transaction_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tickets (id INT AUTO_INCREMENT NOT NULL, draw_id INT NOT NULL, user_id INT NOT NULL, ticket_number INT NOT NULL, purchase_date DATE NOT NULL, status VARCHAR(64) NOT NULL, INDEX IDX_54469DF46FC5C1B8 (draw_id), INDEX IDX_54469DF4A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transactions (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, amount NUMERIC(10, 2) NOT NULL, transaction_date DATE NOT NULL, type VARCHAR(64) NOT NULL, INDEX IDX_EAA81A4CA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE paiements ADD CONSTRAINT FK_E1B02E122FC0CB0F FOREIGN KEY (transaction_id) REFERENCES transactions (id)');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF46FC5C1B8 FOREIGN KEY (draw_id) REFERENCES draws (id)');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF4A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE transactions ADD CONSTRAINT FK_EAA81A4CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE result DROP FOREIGN KEY FK_136AC1136FC5C1B8');
        $this->addSql('ALTER TABLE result DROP FOREIGN KEY FK_136AC11381922061');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA3A76ED395');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA36FC5C1B8');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840DA76ED395');
        $this->addSql('DROP TABLE result');
        $this->addSql('DROP TABLE ticket');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP TABLE draw');
        $this->addSql('ALTER TABLE user ADD registration_date DATE NOT NULL, ADD last_login_date DATE DEFAULT NULL, CHANGE username name VARCHAR(64) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE result (id INT AUTO_INCREMENT NOT NULL, draw_id INT NOT NULL, winner_ticket_id INT NOT NULL, announcement_date DATE NOT NULL, UNIQUE INDEX UNIQ_136AC1136FC5C1B8 (draw_id), UNIQUE INDEX UNIQ_136AC11381922061 (winner_ticket_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE ticket (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, draw_id INT NOT NULL, purchase_date DATE NOT NULL, INDEX IDX_97A0ADA3A76ED395 (user_id), INDEX IDX_97A0ADA36FC5C1B8 (draw_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE payment (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, amount NUMERIC(5, 2) NOT NULL, payment_date DATE NOT NULL, status VARCHAR(64) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_6D28840DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE draw (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(64) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, draw_date DATE NOT NULL, status VARCHAR(64) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE result ADD CONSTRAINT FK_136AC1136FC5C1B8 FOREIGN KEY (draw_id) REFERENCES draw (id)');
        $this->addSql('ALTER TABLE result ADD CONSTRAINT FK_136AC11381922061 FOREIGN KEY (winner_ticket_id) REFERENCES ticket (id)');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA36FC5C1B8 FOREIGN KEY (draw_id) REFERENCES draw (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE notifications DROP FOREIGN KEY FK_6000B0D3A76ED395');
        $this->addSql('ALTER TABLE paiements DROP FOREIGN KEY FK_E1B02E122FC0CB0F');
        $this->addSql('ALTER TABLE tickets DROP FOREIGN KEY FK_54469DF46FC5C1B8');
        $this->addSql('ALTER TABLE tickets DROP FOREIGN KEY FK_54469DF4A76ED395');
        $this->addSql('ALTER TABLE transactions DROP FOREIGN KEY FK_EAA81A4CA76ED395');
        $this->addSql('DROP TABLE draws');
        $this->addSql('DROP TABLE notifications');
        $this->addSql('DROP TABLE paiements');
        $this->addSql('DROP TABLE tickets');
        $this->addSql('DROP TABLE transactions');
        $this->addSql('ALTER TABLE user DROP registration_date, DROP last_login_date, CHANGE name username VARCHAR(64) NOT NULL');
    }
}
