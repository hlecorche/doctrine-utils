<?php

declare(strict_types=1);

/*
 * This file is part of the ecommit/doctrine-utils package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\DoctrineUtils\Paginator;

use Doctrine\DBAL\Query\QueryBuilder;
use Ecommit\DoctrineUtils\QueryBuilderFilter;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @phpstan-import-type CountOptionsWithoutQueryBuilder from DoctrinePaginatorBuilder
 *
 * @phpstan-type PaginatorOptions array{
 *      page?: mixed,
 *      max_per_page?: int,
 *      query_builder: QueryBuilder,
 *      by_identifier?: ?string,
 *      count?: int<0, max>|CountOptionsWithoutQueryBuilder
 * }
 * @phpstan-type PaginatorResolvedOptions array{
 *      page: int<0, max>,
 *      max_per_page: int<0, max>,
 *      query_builder: QueryBuilder,
 *      by_identifier: ?string,
 *      count: int<0, max>|CountOptionsWithoutQueryBuilder
 * }
 *
 * @template-extends AbstractDoctrinePaginator<int, array<string, mixed>, PaginatorOptions, PaginatorResolvedOptions>
 */
final class DoctrineDBALPaginator extends AbstractDoctrinePaginator
{
    protected function buildCount(): int
    {
        $count = $this->getOption('count');
        if (\is_int($count)) {
            return $count;
        }

        return DoctrinePaginatorBuilder::countQueryBuilder(array_merge(
            $count,
            ['query_builder' => $this->getOption('query_builder')]
        ));
    }

    protected function buildIterator(): \Traversable
    {
        if (0 === $this->count()) {
            return new \ArrayIterator([]);
        }

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = clone $this->getOption('query_builder');

        $byIdentifier = $this->getOption('by_identifier');
        if (null === $byIdentifier) {
            $this->setOffsetAndLimit($queryBuilder);
            $result = $queryBuilder->executeQuery();

            return new \ArrayIterator($result->fetchAllAssociative());
        }

        $idsQueryBuilder = clone $queryBuilder;
        $idsQueryBuilder->select(\sprintf('DISTINCT %s as pk', $this->getOption('by_identifier')));
        $this->setOffsetAndLimit($idsQueryBuilder);
        $result = $idsQueryBuilder->executeQuery();

        $ids = $result->fetchFirstColumn();

        $resultsByIdsQueryBuilder = clone $queryBuilder;
        $resultsByIdsQueryBuilder->resetWhere();
        $resultsByIdsQueryBuilder->setParameters([]);
        QueryBuilderFilter::addMultiFilter($resultsByIdsQueryBuilder, QueryBuilderFilter::SELECT_IN, $ids, $byIdentifier, 'paginate_pks');
        $result = $resultsByIdsQueryBuilder->executeQuery();

        return new \ArrayIterator($result->fetchAllAssociative());
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $this->defineDoctrineOptions($resolver);
        $resolver->setAllowedTypes('query_builder', QueryBuilder::class);
    }
}
