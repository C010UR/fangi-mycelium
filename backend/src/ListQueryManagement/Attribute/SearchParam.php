<?php

declare(strict_types=1);

namespace App\ListQueryManagement\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class SearchParam
{
    public const string TYPE_STRING = 'string';
    public const string TYPE_NUMBER = 'number';
    public const string TYPE_ARRAY = 'array';
    public const string TYPE_UUID = 'uuid';

    /**
     * @param string[]|null $roles
     */
    public function __construct(
        public string $name,
        public string $property,
        public ?array $roles = null,
        public string $type = self::TYPE_STRING,
    ) {
    }
}
