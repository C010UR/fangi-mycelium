<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250930123505 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Database setup';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION pg_trgm');

        $this->addSql(<<<SQL
            CREATE OR REPLACE FUNCTION jsonb_array_similarity(
                arr jsonb,
                search_term text
            ) RETURNS float AS $$
            BEGIN
                RETURN COALESCE((
                    SELECT MAX(similarity(elem::text, search_term))
                    FROM jsonb_array_elements_text(arr) as elem
                ), 0);
            END;
            $$ LANGUAGE plpgsql IMMUTABLE;
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP EXTENSION pg_trgm');
        $this->addSql('DROP FUNCTION jsonb_array_similarity');
    }
}
