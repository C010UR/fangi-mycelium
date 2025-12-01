<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251201070303 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Module table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE module_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql(<<<SQL
            CREATE TABLE module (
                id INT NOT NULL,
                created_by_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                image_url TEXT DEFAULT NULL,
                description TEXT DEFAULT NULL,
                client_id UUID NOT NULL,
                urls JSONB NOT NULL,
                is_active BOOLEAN NOT NULL,
                is_banned BOOLEAN NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
            SQL);

        $this->addSql('CREATE INDEX IDX_C242628B03A8386 ON module (created_by_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_MODULE_NAME ON module (name)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_MODULE_CLIENT_ID ON module (client_id)');

        // Search index
        $this->addSql('CREATE INDEX IDX_SEARCH_MODULE_NAME ON module USING gin (LOWER(name) gin_trgm_ops)');
        $this->addSql('CREATE INDEX IDX_SEARCH_MODULE_DESCRIPTION ON module USING gin (LOWER(description) gin_trgm_ops)');

        $this->addSql('COMMENT ON COLUMN module.client_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN module.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN module.updated_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql(<<<SQL
            ALTER TABLE module
                ADD CONSTRAINT FK_C242628B03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE module_id_seq CASCADE');
        $this->addSql('ALTER TABLE module DROP CONSTRAINT FK_C242628B03A8386');
        $this->addSql('DROP TABLE module');
    }
}
