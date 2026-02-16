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

namespace Ecommit\DoctrineUtils\Tests\Paginator;

use Doctrine\DBAL\Query\QueryBuilder as QueryBuilderDBAL;
use Doctrine\ORM\QueryBuilder as QueryBuilderORM;
use Ecommit\DoctrineUtils\Paginator\AbstractDoctrinePaginator;
use Ecommit\DoctrineUtils\Paginator\DoctrineDBALPaginator;
use Ecommit\DoctrineUtils\Paginator\DoctrineORMPaginator;
use Ecommit\DoctrineUtils\Tests\AbstractTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

/**
 * @template TQueryBuilder of QueryBuilderDBAL|QueryBuilderORM
 * @template TPaginator of DoctrineDBALPaginator|DoctrineORMPaginator
 * @template TKey
 *
 * @template-covariant TValue
 *
 * @template TOptions of array<string ,mixed>
 */
abstract class AbstractDoctrinePaginatorTestCase extends AbstractTestCase
{
    /**
     * @return TQueryBuilder
     */
    abstract protected function getDefaultQueryBuilder(): mixed;

    /**
     * @param TOptions $options
     *
     * @return TPaginator
     */
    abstract protected function createPaginator(array $options): AbstractDoctrinePaginator;

    public function testMissingQueryBuilderOption(): void
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('"query_builder"');

        $options = $this->getDefaultOptions();
        unset($options['query_builder']);
        $this->createPaginator($options);
    }

    public function testBadTypeQueryBuilderOption(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('"query_builder"');

        $options = $this->getDefaultOptions();
        $options['query_builder'] = 'string';
        $this->createPaginator($options);
    }

    public function testBadTypeByIdentifierOption(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('"by_identifier"');

        $options = $this->getDefaultOptions();
        $options['by_identifier'] = 1;
        $this->createPaginator($options);
    }

    public function testBadTypeCountOption(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('"count"');

        $options = $this->getDefaultOptions();
        $options['count'] = 'string';
        $this->createPaginator($options);
    }

    public function testBadNumberCountOption(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('"count"');

        $options = $this->getDefaultOptions();
        $options['count'] = -5;
        $this->createPaginator($options);
    }

    /**
     * @param ?TQueryBuilder $queryBuilder
     *
     * @return TOptions
     */
    protected function getDefaultOptions(mixed $page = 1, int $perPage = 5, QueryBuilderDBAL|QueryBuilderORM|null $queryBuilder = null): array
    {
        if (null === $queryBuilder) {
            $queryBuilder = $this->getDefaultQueryBuilder();
        }

        /** @var TOptions $options */
        $options = [
            'page' => $page,
            'max_per_page' => $perPage,
            'query_builder' => $queryBuilder,
        ];

        return $options;
    }

    /**
     * @dataProvider getTestCountProvider
     */
    public function testCount(mixed $page, int $maxPerPage, ?\Closure $queryBuilderUpdater, int $expectedValue): void
    {
        $queryBuilder = $this->getDefaultQueryBuilder();
        if ($queryBuilderUpdater) {
            $queryBuilderUpdater($queryBuilder);
        }
        $this->saveQueryBuilder($queryBuilder);

        $options = $this->getDefaultOptions($page, $maxPerPage, $queryBuilder);
        $paginator = $this->createPaginator($options);

        $this->assertCount($expectedValue, $paginator);
        $this->checkIfQueryBuildNotChange($queryBuilder);
    }

    public static function getTestCountProvider(): array
    {
        $queryBuilderUpdaterNoData = static function (QueryBuilderDBAL|QueryBuilderORM $queryBuilder): void {
            $queryBuilder->andWhere('0 = 1');
        };

        return [
            [1, 5, null, 52],
            [3, 5, null, 52],
            [1, 100, null, 52],
            [1, 5, $queryBuilderUpdaterNoData, 0], // No data
            ['page', 5, null, 52], // Bad page
            [1000, 5, null, 52], // Page too high
        ];
    }

    public function testCountWithIntegerCountOption(): void
    {
        $queryBuilder = $this->getDefaultQueryBuilder();
        $this->saveQueryBuilder($queryBuilder);

        $options = $this->getDefaultOptions(1, 5, $queryBuilder);
        $options['count'] = 8;
        $paginator = $this->createPaginator($options);

        $this->assertCount(8, $paginator);
        $this->assertSame(1, $this->sqlLogger->currentQuery); // Only get results
        $this->checkIfQueryBuildNotChange($queryBuilder);
    }

    public function testCountWithArrayCountOption(): void
    {
        $queryBuilder = $this->getDefaultQueryBuilder();
        $this->saveQueryBuilder($queryBuilder);

        $options = $this->getDefaultOptions(1, 5, $queryBuilder);
        $options['count'] = [
            'behavior' => 'count_by_sub_request',
        ];
        if ($queryBuilder instanceof QueryBuilderDBAL) {
            $options['count']['connection'] = $this->em->getConnection();
        }
        $paginator = $this->createPaginator($options);

        $this->assertCount(52, $paginator);
        $this->assertSame(2, $this->sqlLogger->currentQuery);
        $this->assertStringContainsStringIgnoringCase('mainquery', $this->sqlLogger->queries[1]['sql']);
        $this->checkIfQueryBuildNotChange($queryBuilder);
    }

    public function testNoQueryWhenNoResult(): void
    {
        $queryBuilder = $this->getDefaultQueryBuilder();
        $options = $this->getDefaultOptions(1, 5, $queryBuilder);
        $options['count'] = 0;
        $paginator = $this->createPaginator($options);

        $this->assertSame(0, $this->sqlLogger->currentQuery);
        $this->assertEquals(new \ArrayIterator(), $paginator->getIterator());
    }

    /**
     * @dataProvider getTestItereatorWithoutIdentiferOptionProvider
     */
    public function testItereatorWithoutByIdentiferOption(mixed $page, int $maxPerPage, array $expectedIds, string $expectedRegexSql): void
    {
        $queryBuilder = $this->getDefaultQueryBuilder();
        $this->saveQueryBuilder($queryBuilder);

        $options = $this->getDefaultOptions($page, $maxPerPage, $queryBuilder);
        $options['by_identifier'] = null;
        $paginator = $this->createPaginator($options);

        $this->assertInstanceOf(\ArrayIterator::class, $paginator->getIterator());
        $this->assertSame(2, $this->sqlLogger->currentQuery);
        $this->assertMatchesRegularExpression($expectedRegexSql, $this->sqlLogger->queries[2]['sql']);
        $this->checkEntityIds($paginator, $expectedIds);
        $this->checkIfQueryBuildNotChange($queryBuilder);
    }

    public static function getTestItereatorWithoutIdentiferOptionProvider(): array
    {
        return [
            [1, 5, range(1, 5), '/LIMIT 5$/'],
            [3, 5, range(11, 15), '/LIMIT 5 OFFSET 10$/'],
            [1, 100, range(1, 52), '/LIMIT 100/'],
            ['page', 5, range(1, 5), '/LIMIT 5$/'], // Bad page
            [1000, 5, range(51, 52), '/LIMIT 5 OFFSET 50$/'], // Page too high
        ];
    }
}
