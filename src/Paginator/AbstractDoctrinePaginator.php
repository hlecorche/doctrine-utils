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

use Doctrine\DBAL\Query\QueryBuilder as QueryBuilderDBAL;
use Doctrine\ORM\QueryBuilder as QueryBuilderORM;
use Ecommit\Paginator\AbstractPaginator;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @template TKey
 *
 * @template-covariant TValue
 *
 * @template TOptions of array<string ,mixed>
 * @template TResolvedOptions of array<string ,mixed>
 *
 * @template-extends AbstractPaginator<TKey, TValue, TOptions, TResolvedOptions>
 */
abstract class AbstractDoctrinePaginator extends AbstractPaginator
{
    final protected function setOffsetAndLimit(QueryBuilderDBAL|QueryBuilderORM $queryBuilder): void
    {
        $offset = ($this->getPage() - 1) * $this->getMaxPerPage();
        $queryBuilder->setFirstResult($offset);
        $queryBuilder->setMaxResults($this->getMaxPerPage());
    }

    final protected function defineDoctrineOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('query_builder');
        $resolver->setDefaults([
            'by_identifier' => null,
            'count' => [],
        ]);
        $resolver->setAllowedTypes('by_identifier', ['string', 'null']);
        $resolver->setAllowedTypes('count', ['int', 'array']);
        $resolver->setAllowedValues('count', static fn (int|array $value) => \is_array($value) || $value >= 0);
    }
}
