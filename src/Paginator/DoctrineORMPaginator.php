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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Ecommit\DoctrineUtils\QueryBuilderFilter;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @phpstan-import-type CountOptionsWithoutQueryBuilder from DoctrinePaginatorBuilder
 *
 * @phpstan-type PaginatorOptions array{
 *      page?: mixed,
 *      max_per_page?: int,
 *      query_builder: QueryBuilder,
 *      by_identifier?: ?string,
 *      count?: int<0, max>|CountOptionsWithoutQueryBuilder,
 *      simplified_request?: ?bool,
 *      fetch_join_collection?: ?bool
 * }
 * @phpstan-type PaginatorResolvedOptions array{
 *      page: int<0, max>,
 *      max_per_page: int<0, max>,
 *      query_builder: QueryBuilder,
 *      by_identifier: ?string,
 *      count: int<0, max>|CountOptionsWithoutQueryBuilder,
 *      simplified_request: ?bool,
 *      fetch_join_collection: ?bool
 * }
 *
 * @template-extends AbstractDoctrinePaginator<mixed, mixed, PaginatorOptions, PaginatorResolvedOptions>
 */
final class DoctrineORMPaginator extends AbstractDoctrinePaginator
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
            /** @var bool $fetchJoinCollection */
            $fetchJoinCollection = $this->getOption('fetch_join_collection');
            /** @var bool $simplifiedRequest */
            $simplifiedRequest = $this->getOption('simplified_request');

            $doctrinePaginator = new Paginator($queryBuilder, $fetchJoinCollection);
            $doctrinePaginator->setUseOutputWalkers(!$simplifiedRequest);

            return $doctrinePaginator->getIterator();
        }

        $idsQueryBuilder = clone $queryBuilder;
        $idsQueryBuilder->select(\sprintf('DISTINCT %s as pk', $byIdentifier));
        $this->setOffsetAndLimit($idsQueryBuilder);
        $doctrinePaginator = new Paginator($idsQueryBuilder, false);
        $doctrinePaginator->setUseOutputWalkers(true);
        /** @var array<array-key, array{pk: mixed}> $iterator */
        $iterator = (array) $doctrinePaginator->getIterator();
        $ids = array_map(static fn ($row): mixed => $row['pk'], $iterator);

        $resultsByIdsQueryBuilder = clone $queryBuilder;
        $resultsByIdsQueryBuilder->resetDQLPart('where');
        $resultsByIdsQueryBuilder->setParameters(new ArrayCollection());
        QueryBuilderFilter::addMultiFilter($resultsByIdsQueryBuilder, QueryBuilderFilter::SELECT_IN, $ids, $byIdentifier, 'paginate_pks');
        $result = $resultsByIdsQueryBuilder->getQuery()->getResult();
        if (!\is_array($result)) {
            throw new \Exception('Array expected');
        }

        return new \ArrayIterator($result);
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $this->defineDoctrineOptions($resolver);
        $resolver->setDefaults([
            'simplified_request' => null,
            'fetch_join_collection' => null,
        ]);
        $resolver->setAllowedTypes('query_builder', QueryBuilder::class);
        $resolver->setNormalizer('count', static function (Options $options, int|array $count) {
            if (\is_int($count)) {
                return $count;
            }
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = $options['query_builder'];
            $countBehavior = (isset($count['behavior'])) ? $count['behavior'] : DoctrinePaginatorBuilder::getDefaultCountBehavior($queryBuilder);
            if ('orm' === $countBehavior && !isset($count['simplified_request'])) {
                $count['simplified_request'] = $options['simplified_request'];
            }

            return $count;
        });
        $resolver->setAllowedTypes('simplified_request', ['bool', 'null']);
        $resolver->setNormalizer('simplified_request', static function (Options $options, ?bool $simplifiedRequest): ?bool {
            if (null === $options['by_identifier'] && null === $simplifiedRequest) {
                return true;
            } elseif (null !== $options['by_identifier'] && null !== $simplifiedRequest) {
                throw new InvalidOptionsException('The "simplified_request" option can only be used when "by_identifier" option is not set');
            }

            return $simplifiedRequest;
        });
        $resolver->setAllowedTypes('fetch_join_collection', ['bool', 'null']);
        $resolver->setNormalizer('fetch_join_collection', static function (Options $options, ?bool $fetchJoinCollection): ?bool {
            if (null === $options['by_identifier'] && null === $fetchJoinCollection) {
                return false;
            } elseif (null !== $options['by_identifier'] && null !== $fetchJoinCollection) {
                throw new InvalidOptionsException('The "fetch_join_collection" option can only be used when "by_identifier" option is not set');
            }

            return $fetchJoinCollection;
        });
    }
}
