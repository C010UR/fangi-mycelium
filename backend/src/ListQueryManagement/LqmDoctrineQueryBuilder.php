<?php

declare(strict_types=1);

namespace App\ListQueryManagement;

use App\ListQueryManagement\Attribute as LqmA;
use App\ListQueryManagement\Attribute\SearchParam;
use App\ListQueryManagement\Model\QueryFilter;
use App\ListQueryManagement\Model\QueryOrder;
use App\ListQueryManagement\Model\QueryParamAliasMap;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Uid\Uuid;

class LqmDoctrineQueryBuilder
{
    public const int LIKE_THRESHOLD = 4;

    private string $paramPrefix;
    private int $paramIndex = 0;

    public function __construct(
        private QueryBuilder $query,
        private QueryParamAliasMap $aliasMap,
    ) {
        $this->paramPrefix = bin2hex(random_bytes(6));
    }

    private function nextParam(string $prefix): string
    {
        return \sprintf('%s_%s_%d', $prefix, $this->paramPrefix, $this->paramIndex++);
    }

    /**
     * @param QueryFilter[] $filters
     */
    public function buildFilters(array $filters): static
    {
        foreach ($filters as $filter) {
            if (\is_callable($filter->column)) {
                ($filter->column)($this->query, $filter->operator, $filter->value);
                continue;
            }

            $column = $this->aliasMap->translateColumn($filter->column);

            if (null === $column) {
                continue;
            }

            $expr = new Expr();
            $param = $this->nextParam('request_filter');
            $paramArgument = ':' . $param;

            $comparison = match ($filter->operator) {
                LqmA\FilterOperator::Equal => $expr->eq($column, $paramArgument),
                LqmA\FilterOperator::GreaterThan => $expr->gt($column, $paramArgument),
                LqmA\FilterOperator::GreaterThanOrEqual => $expr->gte($column, $paramArgument),
                LqmA\FilterOperator::LessThan => $expr->lt($column, $paramArgument),
                LqmA\FilterOperator::LessThanOrEqual => $expr->lte($column, $paramArgument),
                LqmA\FilterOperator::Like => $expr->like(\sprintf('LOWER(%s)', $column), $paramArgument),
                LqmA\FilterOperator::In => $expr->in($column, $paramArgument),
                LqmA\FilterOperator::NotIn => $expr->notIn($column, $paramArgument),
                LqmA\FilterOperator::IsNull => $expr->isNull($column),
                LqmA\FilterOperator::IsNotNull => $expr->isNotNull($column),
                LqmA\FilterOperator::True => $expr->eq($column, $paramArgument),
                LqmA\FilterOperator::False => $expr->eq($column, $paramArgument),
            };

            // Only set parameter for operators that need values
            if (!\in_array($filter->operator, [LqmA\FilterOperator::IsNull, LqmA\FilterOperator::IsNotNull], true)) {
                $this->query->setParameter($param, $filter->prepareValueForQuery($filter->operator, $filter->value));
            }

            $this->query->andWhere($comparison);
        }

        return $this;
    }

    /**
     * @param QueryOrder[] $orders
     */
    public function buildOrders(array $orders): static
    {
        foreach ($orders as $order) {
            if (\is_callable($order->column)) {
                ($order->column)($this->query, $order->order);
                continue;
            }

            $column = $this->aliasMap->translateColumn($order->column);

            if (null === $column) {
                continue;
            }

            $this->query->addOrderBy($column, $order->order);
        }

        return $this;
    }

    public function buildDefaultOrder(QueryOrder $order): static
    {
        $column = $this->aliasMap->translateColumn($order->column);

        $this->query->addOrderBy($column);

        return $this;
    }

    /**
     * @param SearchParam[] $searchParams
     */
    public function buildSearch(string $search, array $searchParams, float $similarity): static
    {
        $expr = new Expr();
        $searchExprs = [];

        $searchTokens = explode(' ', $search);
        $canBeNumber = null !== filter_var($search, \FILTER_VALIDATE_FLOAT, \FILTER_NULL_ON_FAILURE)
            || null !== filter_var($search, \FILTER_VALIDATE_INT, \FILTER_NULL_ON_FAILURE);
        $canBeUuid = Uuid::isValid($search);

        $searchParameter = $this->nextParam('request_search');
        $searchNumberParameter = $this->nextParam('request_search');
        $likeParameters = [];

        $useSearchParameter = false;
        $useSearchNumberParameter = false;
        $useSearchLikeParameter = false;

        if (\count($searchTokens) < static::LIKE_THRESHOLD) {
            foreach ($searchTokens as $searchToken) {
                $likeParameters[$this->nextParam('request_search')] = '%' . $searchToken . '%';
            }
        }

        foreach ($searchParams as $searchParam) {
            $column = $this->aliasMap->translateColumn($searchParam->property);

            if (null === $column) {
                continue;
            }

            if (SearchParam::TYPE_STRING === $searchParam->type) {
                $useSearchParameter = true;
                $searchExprs[] = $expr->gt(\sprintf('SEARCH(LOWER(%s), :%s)', $column, $searchParameter), $similarity);

                foreach ($likeParameters as $parameter => $value) {
                    $useSearchLikeParameter =   true;
                    $searchExprs[] = $expr->like(\sprintf('LOWER(%s)', $column), \sprintf(':%s', $parameter));
                }
            } elseif (SearchParam::TYPE_ARRAY === $searchParam->type) {
                $useSearchParameter = true;
                $searchExprs[] = $expr->gt(\sprintf('JSON_ARRAY_SEARCH(%s, :%s)', $column, $searchParameter), $similarity);
            } elseif ($canBeUuid && SearchParam::TYPE_UUID === $searchParam->type) {
                $useSearchParameter = true;
                $searchExprs[] = $expr->eq($column, ':' . $searchParameter);
            } elseif ($canBeNumber && SearchParam::TYPE_NUMBER === $searchParam->type) {
                $useSearchNumberParameter = true;
                $searchExprs[] = $expr->eq($column, ':' . $searchNumberParameter);
            }
        }

        $this->query->andWhere($expr->orX(...$searchExprs));

        if ($useSearchParameter) {
            $this->query->setParameter($searchParameter, $search);
        }

        if ($useSearchNumberParameter) {
            $this->query->setParameter($searchNumberParameter, $search);
        }

        if ($useSearchLikeParameter) {
            foreach ($likeParameters as $parameter => $value) {
                $this->query->setParameter($parameter, $value);
            }
        }

        return $this;
    }

    public function buildPagination(int $page, int $pageSize): static
    {
        if ($pageSize > 0) {
            $this->query
                ->setFirstResult(($page - 1) * $pageSize)
                ->setMaxResults($pageSize);
        }

        return $this;
    }

    public function getQuery(): Query
    {
        return $this->query->getQuery();
    }

    public function getCountQuery(): Query
    {

        if (\is_array($this->aliasMap->getPrimaryAlias())) {
            return (clone $this->query)
                ->select(\sprintf(
                    'COUNT(MULTI_DISTINCT(%s))',
                    implode(', ', $this->aliasMap->getPrimaryAlias()),
                ))
                ->setMaxResults(null)
                ->setFirstResult(null)
                ->resetDQLPart('orderBy')
                ->getQuery();
        } else {
            return (clone $this->query)
                ->select(\sprintf('COUNT(DISTINCT(%s))', $this->aliasMap->getPrimaryAlias()))
                ->setMaxResults(null)
                ->setFirstResult(null)
                ->resetDQLPart('orderBy')
                ->getQuery();
        }
    }
}
