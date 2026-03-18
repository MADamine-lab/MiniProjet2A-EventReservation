<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260223000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des champs email et vérification dans la table user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user`
            ADD email VARCHAR(255) DEFAULT NULL,
            ADD is_verified TINYINT(1) NOT NULL DEFAULT 0,
            ADD verification_token VARCHAR(100) DEFAULT NULL,
            ADD verification_token_expires_at DATETIME DEFAULT NULL
        ');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_USER_EMAIL ON `user` (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_USER_TOKEN ON `user` (verification_token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_USER_EMAIL ON `user`');
        $this->addSql('DROP INDEX UNIQ_USER_TOKEN ON `user`');
        $this->addSql('ALTER TABLE `user`
            DROP email,
            DROP is_verified,
            DROP verification_token,
            DROP verification_token_expires_at
        ');
    }
}
