<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250930123648 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create basic User table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE "user_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql(<<<SQL
            CREATE TABLE "user" (
                id INT NOT NULL,
                created_by_id INT DEFAULT NULL,
                email VARCHAR(180) NOT NULL,
                username VARCHAR(255) NOT NULL,
                image_url TEXT DEFAULT NULL,
                roles JSONB NOT NULL,
                password VARCHAR(255) NOT NULL,
                is_active BOOLEAN NOT NULL,
                is_banned BOOLEAN NOT NULL,
                is_activated BOOLEAN NOT NULL,
                last_login_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
            SQL);

        $this->addSql('CREATE INDEX IDX_8D93D649B03A8386 ON "user" (created_by_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_USER_EMAIL ON "user" (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_USER_USERNAME ON "user" (username)');

        // Search index
        $this->addSql('CREATE INDEX IDX_SEARCH_USER_EMAIL ON "user" USING gin (LOWER(email) gin_trgm_ops)');
        $this->addSql('CREATE INDEX IDX_SEARCH_USER_USERNAME ON "user" USING gin (LOWER(username) gin_trgm_ops)');

        $this->addSql('COMMENT ON COLUMN "user".last_login_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "user".created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "user".updated_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql(<<<SQL
            ALTER TABLE "user"
                ADD CONSTRAINT FK_8D93D649B03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT FK_8D93D649B03A8386');
        $this->addSql('DROP SEQUENCE "user_id_seq" CASCADE');
        $this->addSql('DROP TABLE "user"');
    }
}
