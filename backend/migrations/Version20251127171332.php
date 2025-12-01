<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251127171332 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Server table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE server_id_seq INCREMENT BY 1 MINVALUE 1 START 1');

        $this->addSql(<<<SQL
            CREATE TABLE server (
                id INT NOT NULL,
                created_by_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                image_url TEXT DEFAULT NULL,
                urls JSONB NOT NULL,
                secret VARCHAR(255) NOT NULL,
                auth_token VARCHAR(100) DEFAULT NULL,
                client_id UUID NOT NULL,
                is_active BOOLEAN NOT NULL,
                is_banned BOOLEAN NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
            SQL);

        $this->addSql('CREATE INDEX IDX_5A6DD5F6B03A8386 ON server (created_by_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_SERVER_NAME ON server (name)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_SERVER_CLIENT_ID ON server (client_id)');

        // Search index
        $this->addSql('CREATE INDEX IDX_SEARCH_SERVER_NAME ON server USING gin (LOWER(name) gin_trgm_ops)');

        $this->addSql('COMMENT ON COLUMN server.client_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN server.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN server.updated_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql(<<<SQL
            CREATE TABLE server_user (
                server_id INT NOT NULL,
                user_id INT NOT NULL,
                PRIMARY KEY(server_id, user_id)
            )
            SQL);

        $this->addSql('CREATE INDEX IDX_613A7A91844E6B7 ON server_user (server_id)');
        $this->addSql('CREATE INDEX IDX_613A7A9A76ED395 ON server_user (user_id)');

        $this->addSql(<<<SQL
            ALTER TABLE server ADD CONSTRAINT FK_5A6DD5F6B03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
            SQL);

        $this->addSql(<<<SQL
            ALTER TABLE server_user
                ADD CONSTRAINT FK_613A7A91844E6B7 FOREIGN KEY (server_id) REFERENCES server (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE,
                ADD CONSTRAINT FK_613A7A9A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE server_id_seq CASCADE');
        $this->addSql('ALTER TABLE server DROP CONSTRAINT FK_5A6DD5F6B03A8386');
        $this->addSql('ALTER TABLE server_user DROP CONSTRAINT FK_613A7A91844E6B7');
        $this->addSql('ALTER TABLE server_user DROP CONSTRAINT FK_613A7A9A76ED395');
        $this->addSql('DROP TABLE server');
        $this->addSql('DROP TABLE server_user');
    }
}
