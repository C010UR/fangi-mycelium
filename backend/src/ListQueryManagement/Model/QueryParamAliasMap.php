<?php

declare(strict_types=1);

namespace App\ListQueryManagement\Model;

use InvalidArgumentException;

class QueryParamAliasMap
{
    /**
     * Note: First key will be treated as a primary alias.
     *
     * @param array $mapping Format: ['config_alias' => 'db_alias']
     */
    public function __construct(private string|array $primaryAlias, private array $mapping)
    {
        if (empty($mapping)) {
            throw new InvalidArgumentException('Alias map cannot be empty');
        }
    }

    public function getPrimaryAlias(): array|string
    {
        return $this->primaryAlias;
    }

    public function hasAlias(string $configAlias): bool
    {
        return \array_key_exists($configAlias, $this->mapping);
    }

    public function getDbAlias(string $configAlias): ?string
    {
        return $this->mapping[$configAlias] ?? null;
    }

    public function translateColumn(string $column): ?string
    {
        $pattern = '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/';

        $result = preg_replace_callback($pattern, function ($matches) {
            $alias = $matches[1];

            if (!$this->hasAlias($alias)) {
                throw new InvalidArgumentException(\sprintf('Could not determine DQL name for alias "%s"', $alias));

                return $matches[0];
            }

            return $this->getDbAlias($alias);
        }, $column);

        return $result;
    }

    public function getAllMappings(): array
    {
        return $this->mapping;
    }
}
