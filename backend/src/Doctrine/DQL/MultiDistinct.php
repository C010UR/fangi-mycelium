<?php

declare(strict_types=1);

namespace App\Doctrine\DQL;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\TypedExpression;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

class MultiDistinct extends FunctionNode implements TypedExpression
{
    /** @var Node[] */
    public array $valueExpressions = [];

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER); // MULTI_DISTINCT
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->valueExpressions[] = $parser->ArithmeticPrimary();
        while ($parser->getLexer()->isNextToken(TokenType::T_COMMA)) {
            $parser->match(TokenType::T_COMMA);
            $this->valueExpressions[] = $parser->ArithmeticPrimary();
        }

        if (\count($this->valueExpressions) < 2) {
            throw new QueryException('MULTI_DISTINCT requires at least 2 arguments');
        }

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return \sprintf(
            'DISTINCT(%s)',
            implode(
                ', ',
                array_map(fn(Node $valueExpression) => $valueExpression->dispatch($sqlWalker), $this->valueExpressions),
            ),
        );
    }

    public function getReturnType(): Type
    {
        return Type::getType(Types::BOOLEAN);
    }
}
