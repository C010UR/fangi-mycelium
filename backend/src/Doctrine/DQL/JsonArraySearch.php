<?php

declare(strict_types=1);

namespace App\Doctrine\DQL;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\TypedExpression;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

class JsonArraySearch extends FunctionNode implements TypedExpression
{
    public ?Node $arrayExpression = null;
    public ?Node $searchExpression = null;

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER); // JSON_ARRAY_SEARCH
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->arrayExpression = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_COMMA);
        $this->searchExpression = $parser->ArithmeticPrimary();

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return \sprintf(
            'jsonb_array_similarity(%s, %s)',
            $this->arrayExpression->dispatch($sqlWalker),
            $this->searchExpression->dispatch($sqlWalker),
        );
    }

    public function getReturnType(): Type
    {
        return Type::getType(Types::FLOAT);
    }
}
