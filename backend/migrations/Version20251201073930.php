<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251201073930 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create ServerAllowedModule and UserModuleChoice tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            CREATE TABLE server_allowed_module (
                server_id INT NOT NULL,
                module_id INT NOT NULL,
                created_by_id INT NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(server_id, module_id)
            )
            SQL);

        $this->addSql('CREATE INDEX IDX_8DFF3CE11844E6B7 ON server_allowed_module (server_id)');
        $this->addSql('CREATE INDEX IDX_8DFF3CE1AFC2B591 ON server_allowed_module (module_id)');
        $this->addSql('CREATE INDEX IDX_8DFF3CE1B03A8386 ON server_allowed_module (created_by_id)');

        $this->addSql('COMMENT ON COLUMN server_allowed_module.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN server_allowed_module.updated_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql(<<<SQL
            CREATE TABLE user_module_choice (
                user_id INT NOT NULL,
                module_id INT NOT NULL,
                server_id INT NOT NULL,
                token VARCHAR(100) NOT NULL,
                scopes JSON NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(user_id, module_id)
            )
            SQL);

        $this->addSql('CREATE INDEX IDX_8526B7EDA76ED395 ON user_module_choice (user_id)');
        $this->addSql('CREATE INDEX IDX_8526B7EDAFC2B591 ON user_module_choice (module_id)');
        $this->addSql('CREATE INDEX IDX_8526B7ED1844E6B7 ON user_module_choice (server_id)');

        $this->addSql('COMMENT ON COLUMN user_module_choice.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN user_module_choice.updated_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql(<<<SQL
            ALTER TABLE server_allowed_module
                ADD CONSTRAINT IDX_8DFF3CE11844E6B7 FOREIGN KEY (server_id) REFERENCES server (id) NOT DEFERRABLE INITIALLY IMMEDIATE,
                ADD CONSTRAINT IDX_8DFF3CE1AFC2B591 FOREIGN KEY (module_id) REFERENCES module (id) NOT DEFERRABLE INITIALLY IMMEDIATE,
                ADD CONSTRAINT IDX_8DFF3CE1B03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
            SQL);

        $this->addSql(<<<SQL
            ALTER TABLE user_module_choice
                ADD CONSTRAINT FK_8526B7EDD32632E8 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE,
                ADD CONSTRAINT FK_8526B7EDAFC2B591 FOREIGN KEY (module_id) REFERENCES module (id) NOT DEFERRABLE INITIALLY IMMEDIATE,
                ADD CONSTRAINT FK_8526B7ED1844E6B7 FOREIGN KEY (server_id) REFERENCES server (id) NOT DEFERRABLE INITIALLY IMMEDIATE
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE server_allowed_module DROP CONSTRAINT IDX_8DFF3CE11844E6B7');
        $this->addSql('ALTER TABLE server_allowed_module DROP CONSTRAINT IDX_8DFF3CE1AFC2B591');
        $this->addSql('ALTER TABLE server_allowed_module DROP CONSTRAINT IDX_8DFF3CE1B03A8386');
        $this->addSql('ALTER TABLE user_module_choice DROP CONSTRAINT FK_8526B7EDD32632E8');
        $this->addSql('ALTER TABLE user_module_choice DROP CONSTRAINT FK_8526B7EDAFC2B591');
        $this->addSql('ALTER TABLE user_module_choice DROP CONSTRAINT FK_8526B7ED1844E6B7');
        $this->addSql('DROP TABLE server_allowed_module');
        $this->addSql('DROP TABLE user_module_choice');
    }
}
