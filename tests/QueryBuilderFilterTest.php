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

namespace Ecommit\DoctrineUtils\Tests;

use Doctrine\DBAL\Result;
use Ecommit\DoctrineUtils\QueryBuilderFilter;

class QueryBuilderFilterTest extends AbstractTestCase
{
    public function testAddMultiFilterBadFilterSignDBAL(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Bad filter sign');

        $queryBuilder = $this->createDefaultQueryBuilderDBAL();
        QueryBuilderFilter::addMultiFilter($queryBuilder, 'bad', [], 'e.entity_id', 'entity_id'); // @phpstan-ignore-line
    }

    public function testAddMultiFilterBadFilterSignORM(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Bad filter sign');

        $queryBuilder = $this->createDefaultQueryBuilderORM();
        QueryBuilderFilter::addMultiFilter($queryBuilder, 'bad', [], 'e.entity_id', 'entity_id'); // @phpstan-ignore-line
    }

    /**
     * @param QueryBuilderFilter::SELECT_* $filterSign
     * @param int[]                        $filterValues
     * @param int[]                        $expectedIds
     *
     * @dataProvider getTestAddMultiFilterProvider
     */
    public function testAddMultiFilterDBAL(string $filterSign, array $filterValues, array $expectedIds, int $expectedCountParameters): void
    {
        $queryBuilder = $this->createDefaultQueryBuilderDBAL();
        QueryBuilderFilter::addMultiFilter($queryBuilder, $filterSign, $filterValues, 'e.entity_id', 'entity_id');

        /** @var Result $result */
        $result = $queryBuilder->executeQuery();
        $this->checkEntityIds($result->fetchAllAssociative(), $expectedIds);
        $this->assertSame(1, $this->sqlLogger->currentQuery);
        $this->assertCount($expectedCountParameters, $this->sqlLogger->queries[1]['params']);
    }

    /**
     * @param QueryBuilderFilter::SELECT_* $filterSign
     * @param int[]                        $filterValues
     * @param int[]                        $expectedIds
     *
     * @dataProvider getTestAddMultiFilterProvider
     */
    public function testAddMultiFilterORM(string $filterSign, array $filterValues, array $expectedIds, int $expectedCountParameters): void
    {
        $queryBuilder = $this->createDefaultQueryBuilderORM();
        QueryBuilderFilter::addMultiFilter($queryBuilder, $filterSign, $filterValues, 'e.entityId', 'entity_id');
        $query = $queryBuilder->getQuery();

        /** @var iterable<object> $result */
        $result = $query->getResult();
        $this->checkEntityIds($result, $expectedIds);
        $this->assertSame(1, $this->sqlLogger->currentQuery);
        $this->assertCount($expectedCountParameters, $this->sqlLogger->queries[1]['params']);
    }

    /**
     * @return array<array{QueryBuilderFilter::SELECT_*, int[], int[], int}>
     */
    public static function getTestAddMultiFilterProvider(): array
    {
        $bigValues = array_merge(
            range(10, 1100),
            range(5, 9)
        );

        return [
            // IN
            [QueryBuilderFilter::SELECT_IN, [], [], 1],
            [QueryBuilderFilter::SELECT_IN, [1], [1], 2],
            [QueryBuilderFilter::SELECT_IN, [1, 5, 10], [1, 5, 10], 4],
            [QueryBuilderFilter::SELECT_IN, $bigValues, range(5, 52), 1097],

            // NOT IN
            [QueryBuilderFilter::SELECT_NOT_IN, [], range(1, 52), 1],
            [QueryBuilderFilter::SELECT_NOT_IN, [1], range(2, 52), 2],
            [QueryBuilderFilter::SELECT_NOT_IN, $bigValues, range(1, 4), 1097],

            // NO
            [QueryBuilderFilter::SELECT_NO, [], [], 1],
            [QueryBuilderFilter::SELECT_NO, [1], [], 1],
            [QueryBuilderFilter::SELECT_NO, $bigValues, [], 1],

            // AUTO
            [QueryBuilderFilter::SELECT_AUTO, [], range(1, 52), 1],
            [QueryBuilderFilter::SELECT_AUTO, [1], [1], 2],
            [QueryBuilderFilter::SELECT_AUTO, $bigValues, range(5, 52), 1097],

            // ALL
            [QueryBuilderFilter::SELECT_ALL, [], range(1, 52), 1],
            [QueryBuilderFilter::SELECT_ALL, [1], range(1, 52), 1],
            [QueryBuilderFilter::SELECT_ALL, $bigValues, range(1, 52), 1],
        ];
    }

    public function testAddMultiFilterWithRestrictValuesBadFilterSignDBAL(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Bad filter sign');

        $queryBuilder = $this->createDefaultQueryBuilderDBAL();
        QueryBuilderFilter::addMultiFilterWithRestrictValues($queryBuilder, 'bad', [], 'e.entity_id', 'entity_id', QueryBuilderFilter::SELECT_ALL, []); // @phpstan-ignore-line
    }

    public function testAddMultiFilterWithRestrictValuesBadFilterSignORM(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Bad filter sign');

        $queryBuilder = $this->createDefaultQueryBuilderORM();
        QueryBuilderFilter::addMultiFilterWithRestrictValues($queryBuilder, 'bad', [], 'e.entity_id', 'entity_id', QueryBuilderFilter::SELECT_ALL, []); // @phpstan-ignore-line
    }

    public function testAddMultiFilterWithRestrictValuesBadRestrictFilterSignDBAL(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Bad filter sign');

        $queryBuilder = $this->createDefaultQueryBuilderDBAL();
        QueryBuilderFilter::addMultiFilterWithRestrictValues($queryBuilder, QueryBuilderFilter::SELECT_IN, [], 'e.entity_id', 'entity_id', 'bad', []); // @phpstan-ignore-line
    }

    public function testAddMultiFilterWithRestrictValuesBadRestrictFilterSignORM(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Bad filter sign');

        $queryBuilder = $this->createDefaultQueryBuilderORM();
        QueryBuilderFilter::addMultiFilterWithRestrictValues($queryBuilder, QueryBuilderFilter::SELECT_IN, [], 'e.entity_id', 'entity_id', 'bad', []); // @phpstan-ignore-line
    }

    /**
     * @param QueryBuilderFilter::SELECT_* $filterSign
     * @param int[]                        $filterValues
     * @param QueryBuilderFilter::SELECT_* $restricSign
     * @param int[]                        $restrictValues
     * @param int[]                        $expectedIds
     *
     * @dataProvider getTestAddMultiFilterWithRestrictValuesProvider
     */
    public function testAddMultiFilterWithRestrictValuesDBAL(string $filterSign, array $filterValues, string $restricSign, array $restrictValues, array $expectedIds): void
    {
        $queryBuilder = $this->createDefaultQueryBuilderDBAL();
        QueryBuilderFilter::addMultiFilterWithRestrictValues($queryBuilder, $filterSign, $filterValues, 'e.entity_id', 'entity_id', $restricSign, $restrictValues);

        /** @var Result $result */
        $result = $queryBuilder->executeQuery();
        $this->checkEntityIds($result->fetchAllAssociative(), $expectedIds);
        $this->assertSame(1, $this->sqlLogger->currentQuery);
    }

    /**
     * @param QueryBuilderFilter::SELECT_* $filterSign
     * @param int[]                        $filterValues
     * @param QueryBuilderFilter::SELECT_* $restricSign
     * @param int[]                        $restrictValues
     * @param int[]                        $expectedIds
     *
     * @dataProvider getTestAddMultiFilterWithRestrictValuesProvider
     */
    public function testAddMultiFilterWithRestrictValuesORM(string $filterSign, array $filterValues, string $restricSign, array $restrictValues, array $expectedIds): void
    {
        $queryBuilder = $this->createDefaultQueryBuilderORM();
        QueryBuilderFilter::addMultiFilterWithRestrictValues($queryBuilder, $filterSign, $filterValues, 'e.entityId', 'entity_id', $restricSign, $restrictValues);
        $query = $queryBuilder->getQuery();

        /** @var iterable<object> $result */
        $result = $query->getResult();
        $this->checkEntityIds($result, $expectedIds);
        $this->assertSame(1, $this->sqlLogger->currentQuery);
    }

    /**
     * @return array<array{QueryBuilderFilter::SELECT_*, int[], QueryBuilderFilter::SELECT_*, int[], int[]}>
     */
    public static function getTestAddMultiFilterWithRestrictValuesProvider(): array
    {
        return [
            // IN - WITHOUT VALUES
            [QueryBuilderFilter::SELECT_IN, [], QueryBuilderFilter::SELECT_IN, [], []],
            [QueryBuilderFilter::SELECT_IN, [], QueryBuilderFilter::SELECT_IN, [1], []],
            [QueryBuilderFilter::SELECT_IN, [], QueryBuilderFilter::SELECT_NOT_IN, [], []],
            [QueryBuilderFilter::SELECT_IN, [], QueryBuilderFilter::SELECT_NOT_IN, [1], []],
            [QueryBuilderFilter::SELECT_IN, [], QueryBuilderFilter::SELECT_NO, [], []],
            [QueryBuilderFilter::SELECT_IN, [], QueryBuilderFilter::SELECT_NO, [1], []],
            [QueryBuilderFilter::SELECT_IN, [], QueryBuilderFilter::SELECT_AUTO, [], []],
            [QueryBuilderFilter::SELECT_IN, [], QueryBuilderFilter::SELECT_AUTO, [1], []],
            [QueryBuilderFilter::SELECT_IN, [], QueryBuilderFilter::SELECT_ALL, [], []],
            [QueryBuilderFilter::SELECT_IN, [], QueryBuilderFilter::SELECT_ALL, [1], []],

            // IN - WITH VALUES
            [QueryBuilderFilter::SELECT_IN, [1, 10, 20], QueryBuilderFilter::SELECT_IN, [], []],
            [QueryBuilderFilter::SELECT_IN, [1, 10, 20], QueryBuilderFilter::SELECT_IN, [1], [1]],
            [QueryBuilderFilter::SELECT_IN, [1, 10, 20], QueryBuilderFilter::SELECT_NOT_IN, [], [1, 10, 20]],
            [QueryBuilderFilter::SELECT_IN, [1, 10, 20], QueryBuilderFilter::SELECT_NOT_IN, [1], [10, 20]],
            [QueryBuilderFilter::SELECT_IN, [1, 10, 20], QueryBuilderFilter::SELECT_NO, [], []],
            [QueryBuilderFilter::SELECT_IN, [1, 10, 20], QueryBuilderFilter::SELECT_NO, [1], []],
            [QueryBuilderFilter::SELECT_IN, [1, 10, 20], QueryBuilderFilter::SELECT_AUTO, [], [1, 10, 20]],
            [QueryBuilderFilter::SELECT_IN, [1, 10, 20], QueryBuilderFilter::SELECT_AUTO, [1], [1]],
            [QueryBuilderFilter::SELECT_IN, [1, 10, 20], QueryBuilderFilter::SELECT_ALL, [], [1, 10, 20]],
            [QueryBuilderFilter::SELECT_IN, [1, 10, 20], QueryBuilderFilter::SELECT_ALL, [1], [1, 10, 20]],

            // NOT IN - WITHOUT VALUES
            [QueryBuilderFilter::SELECT_NOT_IN, [], QueryBuilderFilter::SELECT_IN, [], []],
            [QueryBuilderFilter::SELECT_NOT_IN, [], QueryBuilderFilter::SELECT_IN, [1], [1]],
            [QueryBuilderFilter::SELECT_NOT_IN, [], QueryBuilderFilter::SELECT_NOT_IN, [], range(1, 52)],
            [QueryBuilderFilter::SELECT_NOT_IN, [], QueryBuilderFilter::SELECT_NOT_IN, [1], range(2, 52)],
            [QueryBuilderFilter::SELECT_NOT_IN, [], QueryBuilderFilter::SELECT_NO, [], []],
            [QueryBuilderFilter::SELECT_NOT_IN, [], QueryBuilderFilter::SELECT_NO, [1], []],
            [QueryBuilderFilter::SELECT_NOT_IN, [], QueryBuilderFilter::SELECT_AUTO, [], range(1, 52)],
            [QueryBuilderFilter::SELECT_NOT_IN, [], QueryBuilderFilter::SELECT_AUTO, [1], [1]],
            [QueryBuilderFilter::SELECT_NOT_IN, [], QueryBuilderFilter::SELECT_ALL, [], range(1, 52)],
            [QueryBuilderFilter::SELECT_NOT_IN, [], QueryBuilderFilter::SELECT_ALL, [1], range(1, 52)],

            // NOT IN - WITH VALUES
            [QueryBuilderFilter::SELECT_NOT_IN, [1], QueryBuilderFilter::SELECT_IN, [], []],
            [QueryBuilderFilter::SELECT_NOT_IN, [1], QueryBuilderFilter::SELECT_IN, [1, 10, 20], [10, 20]],
            [QueryBuilderFilter::SELECT_NOT_IN, [1], QueryBuilderFilter::SELECT_NOT_IN, [], range(2, 52)],
            [QueryBuilderFilter::SELECT_NOT_IN, [1], QueryBuilderFilter::SELECT_NOT_IN, [1, 10, 20], array_merge(range(2, 9), range(11, 19), range(21, 52))],
            [QueryBuilderFilter::SELECT_NOT_IN, [1], QueryBuilderFilter::SELECT_NO, [], []],
            [QueryBuilderFilter::SELECT_NOT_IN, [1], QueryBuilderFilter::SELECT_NO, [1, 10, 20], []],
            [QueryBuilderFilter::SELECT_NOT_IN, [1], QueryBuilderFilter::SELECT_AUTO, [], range(2, 52)],
            [QueryBuilderFilter::SELECT_NOT_IN, [1], QueryBuilderFilter::SELECT_AUTO, [1, 10, 20], [10, 20]],
            [QueryBuilderFilter::SELECT_NOT_IN, [1], QueryBuilderFilter::SELECT_ALL, [], range(2, 52)],
            [QueryBuilderFilter::SELECT_NOT_IN, [1], QueryBuilderFilter::SELECT_ALL, [1, 20, 20], range(2, 52)],

            // NO - WITHOUT VALUES
            [QueryBuilderFilter::SELECT_NO, [], QueryBuilderFilter::SELECT_IN, [], []],
            [QueryBuilderFilter::SELECT_NO, [], QueryBuilderFilter::SELECT_IN, [1], []],
            [QueryBuilderFilter::SELECT_NO, [], QueryBuilderFilter::SELECT_NOT_IN, [], []],
            [QueryBuilderFilter::SELECT_NO, [], QueryBuilderFilter::SELECT_NOT_IN, [1], []],
            [QueryBuilderFilter::SELECT_NO, [], QueryBuilderFilter::SELECT_NO, [], []],
            [QueryBuilderFilter::SELECT_NO, [], QueryBuilderFilter::SELECT_NO, [1], []],
            [QueryBuilderFilter::SELECT_NO, [], QueryBuilderFilter::SELECT_AUTO, [], []],
            [QueryBuilderFilter::SELECT_NO, [], QueryBuilderFilter::SELECT_AUTO, [1], []],
            [QueryBuilderFilter::SELECT_NO, [], QueryBuilderFilter::SELECT_ALL, [], []],
            [QueryBuilderFilter::SELECT_NO, [], QueryBuilderFilter::SELECT_ALL, [1], []],

            // NO - WITH VALUES
            [QueryBuilderFilter::SELECT_NO, [1, 10, 20], QueryBuilderFilter::SELECT_IN, [], []],
            [QueryBuilderFilter::SELECT_NO, [1, 10, 20], QueryBuilderFilter::SELECT_IN, [1], []],
            [QueryBuilderFilter::SELECT_NO, [1, 10, 20], QueryBuilderFilter::SELECT_NOT_IN, [], []],
            [QueryBuilderFilter::SELECT_NO, [1, 10, 20], QueryBuilderFilter::SELECT_NOT_IN, [1], []],
            [QueryBuilderFilter::SELECT_NO, [1, 10, 20], QueryBuilderFilter::SELECT_NO, [], []],
            [QueryBuilderFilter::SELECT_NO, [1, 10, 20], QueryBuilderFilter::SELECT_NO, [1], []],
            [QueryBuilderFilter::SELECT_NO, [1, 10, 20], QueryBuilderFilter::SELECT_AUTO, [], []],
            [QueryBuilderFilter::SELECT_NO, [1, 10, 20], QueryBuilderFilter::SELECT_AUTO, [1], []],
            [QueryBuilderFilter::SELECT_NO, [1, 10, 20], QueryBuilderFilter::SELECT_ALL, [], []],
            [QueryBuilderFilter::SELECT_NO, [1, 10, 20], QueryBuilderFilter::SELECT_ALL, [1], []],

            // AUTO - WITHOUT VALUES
            [QueryBuilderFilter::SELECT_AUTO, [], QueryBuilderFilter::SELECT_IN, [], []],
            [QueryBuilderFilter::SELECT_AUTO, [], QueryBuilderFilter::SELECT_IN, [1], [1]],
            [QueryBuilderFilter::SELECT_AUTO, [], QueryBuilderFilter::SELECT_NOT_IN, [], range(1, 52)],
            [QueryBuilderFilter::SELECT_AUTO, [], QueryBuilderFilter::SELECT_NOT_IN, [1], range(2, 52)],
            [QueryBuilderFilter::SELECT_AUTO, [], QueryBuilderFilter::SELECT_NO, [], []],
            [QueryBuilderFilter::SELECT_AUTO, [], QueryBuilderFilter::SELECT_NO, [1], []],
            [QueryBuilderFilter::SELECT_AUTO, [], QueryBuilderFilter::SELECT_AUTO, [], range(1, 52)],
            [QueryBuilderFilter::SELECT_AUTO, [], QueryBuilderFilter::SELECT_AUTO, [1], [1]],
            [QueryBuilderFilter::SELECT_AUTO, [], QueryBuilderFilter::SELECT_ALL, [], range(1, 52)],
            [QueryBuilderFilter::SELECT_AUTO, [], QueryBuilderFilter::SELECT_ALL, [1], range(1, 52)],

            // AUTO - WITH VALUES
            [QueryBuilderFilter::SELECT_AUTO, [1, 10, 20], QueryBuilderFilter::SELECT_IN, [], []],
            [QueryBuilderFilter::SELECT_AUTO, [1, 10, 20], QueryBuilderFilter::SELECT_IN, [1], [1]],
            [QueryBuilderFilter::SELECT_AUTO, [1, 10, 20], QueryBuilderFilter::SELECT_NOT_IN, [], [1, 10, 20]],
            [QueryBuilderFilter::SELECT_AUTO, [1, 10, 20], QueryBuilderFilter::SELECT_NOT_IN, [1], [10, 20]],
            [QueryBuilderFilter::SELECT_AUTO, [1, 10, 20], QueryBuilderFilter::SELECT_NO, [], []],
            [QueryBuilderFilter::SELECT_AUTO, [1, 10, 20], QueryBuilderFilter::SELECT_NO, [1], []],
            [QueryBuilderFilter::SELECT_AUTO, [1, 10, 20], QueryBuilderFilter::SELECT_AUTO, [], [1, 10, 20]],
            [QueryBuilderFilter::SELECT_AUTO, [1, 10, 20], QueryBuilderFilter::SELECT_AUTO, [1], [1]],
            [QueryBuilderFilter::SELECT_AUTO, [1, 10, 20], QueryBuilderFilter::SELECT_ALL, [], [1, 10, 20]],
            [QueryBuilderFilter::SELECT_AUTO, [1, 10, 20], QueryBuilderFilter::SELECT_ALL, [1], [1, 10, 20]],

            // ALL - WITHOUT VALUES
            [QueryBuilderFilter::SELECT_ALL, [], QueryBuilderFilter::SELECT_IN, [], []],
            [QueryBuilderFilter::SELECT_ALL, [], QueryBuilderFilter::SELECT_IN, [1], [1]],
            [QueryBuilderFilter::SELECT_ALL, [], QueryBuilderFilter::SELECT_NOT_IN, [], range(1, 52)],
            [QueryBuilderFilter::SELECT_ALL, [], QueryBuilderFilter::SELECT_NOT_IN, [1], range(2, 52)],
            [QueryBuilderFilter::SELECT_ALL, [], QueryBuilderFilter::SELECT_NO, [], []],
            [QueryBuilderFilter::SELECT_ALL, [], QueryBuilderFilter::SELECT_NO, [1], []],
            [QueryBuilderFilter::SELECT_ALL, [], QueryBuilderFilter::SELECT_AUTO, [], range(1, 52)],
            [QueryBuilderFilter::SELECT_ALL, [], QueryBuilderFilter::SELECT_AUTO, [1], [1]],
            [QueryBuilderFilter::SELECT_ALL, [], QueryBuilderFilter::SELECT_ALL, [], range(1, 52)],
            [QueryBuilderFilter::SELECT_ALL, [], QueryBuilderFilter::SELECT_ALL, [1], range(1, 52)],

            // ALL - WITH VALUES
            [QueryBuilderFilter::SELECT_ALL, [1, 10, 20], QueryBuilderFilter::SELECT_IN, [], []],
            [QueryBuilderFilter::SELECT_ALL, [1, 10, 20], QueryBuilderFilter::SELECT_IN, [1], [1]],
            [QueryBuilderFilter::SELECT_ALL, [1, 10, 20], QueryBuilderFilter::SELECT_NOT_IN, [], range(1, 52)],
            [QueryBuilderFilter::SELECT_ALL, [1, 10, 20], QueryBuilderFilter::SELECT_NOT_IN, [1], range(2, 52)],
            [QueryBuilderFilter::SELECT_ALL, [1, 10, 20], QueryBuilderFilter::SELECT_NO, [], []],
            [QueryBuilderFilter::SELECT_ALL, [1, 10, 20], QueryBuilderFilter::SELECT_NO, [1], []],
            [QueryBuilderFilter::SELECT_ALL, [1, 10, 20], QueryBuilderFilter::SELECT_AUTO, [], range(1, 52)],
            [QueryBuilderFilter::SELECT_ALL, [1, 10, 20], QueryBuilderFilter::SELECT_AUTO, [1], [1]],
            [QueryBuilderFilter::SELECT_ALL, [1, 10, 20], QueryBuilderFilter::SELECT_ALL, [], range(1, 52)],
            [QueryBuilderFilter::SELECT_ALL, [1, 10, 20], QueryBuilderFilter::SELECT_ALL, [1], range(1, 52)],
        ];
    }

    /**
     * @param int[] $expectedIds
     *
     * @dataProvider getTestAddEqualFilterProvider
     */
    public function testAddEqualFilterDBAL(bool $equal, mixed $value, array $expectedIds, int $expectedCountParameters): void
    {
        $queryBuilder = $this->createDefaultQueryBuilderDBAL();
        QueryBuilderFilter::addEqualFilter($queryBuilder, $equal, $value, 'e.entity_id', 'entity_id');

        /** @var Result $result */
        $result = $queryBuilder->executeQuery();
        $this->checkEntityIds($result->fetchAllAssociative(), $expectedIds);
        $this->assertSame(1, $this->sqlLogger->currentQuery);
        $this->assertCount($expectedCountParameters, $this->sqlLogger->queries[1]['params']);
    }

    /**
     * @param int[] $expectedIds
     *
     * @dataProvider getTestAddEqualFilterProvider
     */
    public function testAddEqualFilterORM(bool $equal, mixed $value, array $expectedIds, int $expectedCountParameters): void
    {
        $queryBuilder = $this->createDefaultQueryBuilderORM();
        QueryBuilderFilter::addEqualFilter($queryBuilder, $equal, $value, 'e.entityId', 'entity_id');
        $query = $queryBuilder->getQuery();

        /** @var iterable<object> $result */
        $result = $query->getResult();
        $this->checkEntityIds($result, $expectedIds);
        $this->assertSame(1, $this->sqlLogger->currentQuery);
        $this->assertCount($expectedCountParameters, $this->sqlLogger->queries[1]['params']);
    }

    /**
     * @return array<array{bool, mixed, int[], int}>
     */
    public static function getTestAddEqualFilterProvider(): array
    {
        return [
            // EQUAL - TRUE
            [true, 10, [10], 2],
            [true, '10', [10], 2],
            [true, null, range(1, 52), 1],
            [true, '', range(1, 52), 1],

            // EQUAL - FALSE
            [false, 1, range(2, 52), 2],
            [false, '1', range(2, 52), 2],
            [false, null, range(1, 52), 1],
            [false, '', range(1, 52), 1],
        ];
    }

    /**
     * @param '<'|'>'|'<='|'>=' $sign
     * @param int[]             $expectedIds
     *
     * @dataProvider getTestAddComparatorFilterProvider
     */
    public function testAddComparatorFilterDBAL(string $sign, mixed $value, array $expectedIds, int $expectedCountParameters): void
    {
        $queryBuilder = $this->createDefaultQueryBuilderDBAL();
        QueryBuilderFilter::addComparatorFilter($queryBuilder, $sign, $value, 'e.entity_id', 'entity_id');

        /** @var Result $result */
        $result = $queryBuilder->executeQuery();
        $this->checkEntityIds($result->fetchAllAssociative(), $expectedIds);
        $this->assertSame(1, $this->sqlLogger->currentQuery);
        $this->assertCount($expectedCountParameters, $this->sqlLogger->queries[1]['params']);
    }

    /**
     * @param '<'|'>'|'<='|'>=' $sign
     * @param int[]             $expectedIds
     *
     * @dataProvider getTestAddComparatorFilterProvider
     */
    public function testAddComparatorFilterORM(string $sign, mixed $value, array $expectedIds, int $expectedCountParameters): void
    {
        $queryBuilder = $this->createDefaultQueryBuilderORM();
        QueryBuilderFilter::addComparatorFilter($queryBuilder, $sign, $value, 'e.entityId', 'entity_id');
        $query = $queryBuilder->getQuery();

        /** @var iterable<object> $result */
        $result = $query->getResult();
        $this->checkEntityIds($result, $expectedIds);
        $this->assertSame(1, $this->sqlLogger->currentQuery);
        $this->assertCount($expectedCountParameters, $this->sqlLogger->queries[1]['params']);
    }

    /**
     * @return array<array{'<'|'>'|'<='|'>=', mixed, int[], int}>
     */
    public static function getTestAddComparatorFilterProvider(): array
    {
        return [
            ['>', 1, range(2, 52), 2],
            ['>', '1', range(2, 52), 2],
            ['>', null, range(1, 52), 1],
            ['>', '', range(1, 52), 1],
        ];
    }

    /**
     * @param int[] $expectedIds
     *
     * @dataProvider getTestAddContainFilterProvider
     */
    public function testAddContainFilterDBAL(bool $contain, ?string $value, array $expectedIds, int $expectedCountParameters): void
    {
        $queryBuilder = $this->createDefaultQueryBuilderDBAL();
        QueryBuilderFilter::addContainFilter($queryBuilder, $contain, $value, 'e.title', 'title');

        /** @var Result $result */
        $result = $queryBuilder->executeQuery();
        $this->checkEntityIds($result->fetchAllAssociative(), $expectedIds);
        $this->assertSame(1, $this->sqlLogger->currentQuery);
        $this->assertCount($expectedCountParameters, $this->sqlLogger->queries[1]['params']);
    }

    /**
     * @param int[] $expectedIds
     *
     * @dataProvider getTestAddContainFilterProvider
     */
    public function testAddContainFilterORM(bool $contain, ?string $value, array $expectedIds, int $expectedCountParameters): void
    {
        $queryBuilder = $this->createDefaultQueryBuilderORM();
        QueryBuilderFilter::addContainFilter($queryBuilder, $contain, $value, 'e.title', 'title');
        $query = $queryBuilder->getQuery();

        /** @var iterable<object> $result */
        $result = $query->getResult();
        $this->checkEntityIds($result, $expectedIds);
        $this->assertSame(1, $this->sqlLogger->currentQuery);
        $this->assertCount($expectedCountParameters, $this->sqlLogger->queries[1]['params']);
    }

    /**
     * @return array<array{bool, ?string, int[], int}>
     */
    public static function getTestAddContainFilterProvider(): array
    {
        return [
            // CONTAIN - TRUE
            [true, 'Entity 5', [5, 50, 51, 52], 2],
            [true, '_ntity', [], 2],
            [true, 'Entity %', [], 2],
            [true, '', range(1, 52), 1],
            [true, null, range(1, 52), 1],

            // CONTAIN - FALSE
            [false, 'Entity 5', array_merge(range(1, 4), range(6, 49)), 2],
            [false, '_ntity', range(1, 52), 2],
            [false, 'Entity %', range(1, 52), 2],
            [false, '', range(1, 52), 1],
            [false, null, range(1, 52), 1],
        ];
    }
}
