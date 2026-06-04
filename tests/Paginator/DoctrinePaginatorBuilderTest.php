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

use Ecommit\DoctrineUtils\Paginator\DoctrineDBALPaginator;
use Ecommit\DoctrineUtils\Paginator\DoctrineORMPaginator;
use Ecommit\DoctrineUtils\Paginator\DoctrinePaginatorBuilder;
use Ecommit\DoctrineUtils\Tests\AbstractTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class DoctrinePaginatorBuilderTest extends AbstractTestCase
{
    public function testCountQueryBuilderBadQueryBuilder(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('"query_builder"');

        // @phpstan-ignore-next-line
        DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => 'bad',
        ]);
    }

    /**
     * @dataProvider getTestCountQueryBuilderDBALBadBehaviorProvider
     */
    public function testCountQueryBuilderDBALBadBehavior(string $behavior): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('"behavior"');

        // @phpstan-ignore-next-line
        DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => $this->createDefaultQueryBuilderDBAL(),
            'behavior' => $behavior,
        ]);
    }

    /**
     * @return array<array{string}>
     */
    public static function getTestCountQueryBuilderDBALBadBehaviorProvider(): array
    {
        return [
            ['bad'],
            ['orm'],
        ];
    }

    /**
     * @dataProvider getTestCountQueryBuilderORMBadBehaviorProvider
     */
    public function testCountQueryBuilderORMBadBehavior(string $behavior): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('"behavior"');

        // @phpstan-ignore-next-line
        DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => $this->createDefaultQueryBuilderORM(),
            'behavior' => $behavior,
        ]);
    }

    /**
     * @return array<array{string}>
     */
    public static function getTestCountQueryBuilderORMBadBehaviorProvider(): array
    {
        return [
            ['bad'],
            ['count_by_select_all'],
        ];
    }

    public function testCountQueryBuilderDBALDefaultBehavior(): void
    {
        DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => $this->createDefaultQueryBuilderDBAL(),
            'connection' => $this->em->getConnection(),
        ]);

        $this->assertSame(1, $this->sqlLogger->currentQuery);
        $this->assertStringContainsStringIgnoringCase('SELECT count(*)', $this->sqlLogger->queries[1]['sql']);
        $this->assertStringNotContainsStringIgnoringCase('mainquery', $this->sqlLogger->queries[1]['sql']);
    }

    public function testCountQueryBuilderORMDefaultBehavior(): void
    {
        DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => $this->createDefaultQueryBuilderORM(),
        ]);

        $this->assertSame(1, $this->sqlLogger->currentQuery);
        $this->assertStringNotContainsStringIgnoringCase('ORDER', $this->sqlLogger->queries[1]['sql']);
    }

    public function testCountQueryBuilderDBALBadAliasOption(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('"alias"');

        // @phpstan-ignore-next-line
        DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => $this->createDefaultQueryBuilderDBAL(),
            'behavior' => 'count_by_alias',
            'alias' => [],
        ]);
    }

    public function testCountQueryBuilderORMBadAliasOption(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('"alias"');

        // @phpstan-ignore-next-line
        DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => $this->createDefaultQueryBuilderORM(),
            'behavior' => 'count_by_alias',
            'alias' => [],
        ]);
    }

    public function testCountQueryBuilderDBALCountByAliasMissingAliasOption(): void
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('When "behavior" option is set to "count_by_alias", "alias" option is required');

        DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => $this->createDefaultQueryBuilderDBAL(),
            'behavior' => 'count_by_alias',
        ]);
    }

    public function testCountQueryBuilderORMCountByAliasMissingAliasOption(): void
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('When "behavior" option is set to "count_by_alias", "alias" option is required');

        DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => $this->createDefaultQueryBuilderORM(),
            'behavior' => 'count_by_alias',
        ]);
    }

    public function testCountQueryBuilderDBALAliasOptionNotAllowed(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The "alias" option can only be used when "behavior" option is set to "count_by_alias"');

        DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => $this->createDefaultQueryBuilderDBAL(),
            'behavior' => 'count_by_sub_request',
            'alias' => 'alias',
            'connection' => $this->em->getConnection(),
        ]);
    }

    /**
     * @dataProvider getTestCountQueryBuilderORMAliasOptionNotAllowedProvider
     */
    public function testCountQueryBuilderORMAliasOptionNotAllowed(string $behavior): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The "alias" option can only be used when "behavior" option is set to "count_by_alias"');

        // @phpstan-ignore-next-line
        DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => $this->createDefaultQueryBuilderORM(),
            'behavior' => $behavior,
            'alias' => 'alias',
        ]);
    }

    /**
     * @return array<array{string}>
     */
    public static function getTestCountQueryBuilderORMAliasOptionNotAllowedProvider(): array
    {
        return [
            ['count_by_sub_request'],
            ['orm'],
        ];
    }

    public function testCountQueryBuilderDBALBadDistinctAliasOption(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('"distinct_alias"');

        // @phpstan-ignore-next-line
        DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => $this->createDefaultQueryBuilderDBAL(),
            'behavior' => 'count_by_alias',
            'alias' => 'alias',
            'distinct_alias' => 'bad',
        ]);
    }

    public function testCountQueryBuilderORMBadDistinctAliasOption(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('"distinct_alias"');

        // @phpstan-ignore-next-line
        DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => $this->createDefaultQueryBuilderORM(),
            'behavior' => 'count_by_alias',
            'alias' => 'alias',
            'distinct_alias' => 'bad',
        ]);
    }

    public function testCountQueryBuilderDBALDefaultDistinctAliasOption(): void
    {
        DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => $this->createDefaultQueryBuilderDBAL(),
            'behavior' => 'count_by_alias',
            'alias' => 'e.entity_id',
        ]);

        $this->assertSame(1, $this->sqlLogger->currentQuery);
        $this->assertStringContainsStringIgnoringCase('DISTINCT', $this->sqlLogger->queries[1]['sql']);
    }

    public function testCountQueryBuilderORMDefaultDistinctAliasOption(): void
    {
        DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => $this->createDefaultQueryBuilderORM(),
            'behavior' => 'count_by_alias',
            'alias' => 'e.entityId',
        ]);

        $this->assertSame(1, $this->sqlLogger->currentQuery);
        $this->assertStringContainsStringIgnoringCase('DISTINCT', $this->sqlLogger->queries[1]['sql']);
    }

    public function testCountQueryBuilderDBALDistinctAliasOptionNotAllowed(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The "distinct_alias" option can only be used when "behavior" option is set to "count_by_alias"');

        DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => $this->createDefaultQueryBuilderDBAL(),
            'behavior' => 'count_by_sub_request',
            'distinct_alias' => true,
            'connection' => $this->em->getConnection(),
        ]);
    }

    /**
     * @dataProvider getTestCountQueryBuilderORMAliasOptionNotAllowedProvider
     */
    public function testCountQueryBuilderORMDistinctAliasOptionNotAllowed(string $behavior): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The "distinct_alias" option can only be used when "behavior" option is set to "count_by_alias"');

        // @phpstan-ignore-next-line
        DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => $this->createDefaultQueryBuilderORM(),
            'behavior' => $behavior,
            'distinct_alias' => true,
        ]);
    }

    public function testCountQueryBuilderORMBadSimplfiedRequestOption(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('"simplified_request"');

        // @phpstan-ignore-next-line
        DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => $this->createDefaultQueryBuilderORM(),
            'behavior' => 'orm',
            'simplified_request' => 'bad',
        ]);
    }

    public function testCountQueryBuilderORMDefaultSimplifiedRequestOption(): void
    {
        DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => $this->createDefaultQueryBuilderORM(),
            'behavior' => 'orm',
        ]);

        $this->assertSame(1, $this->sqlLogger->currentQuery);
        $this->assertStringNotContainsStringIgnoringCase('ORDER', $this->sqlLogger->queries[1]['sql']);
    }

    public function testCountQueryBuilderDBALSimplifiedRequestOptionNotAllowed(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The "simplified_request" option can only be used with ORM QueryBuilder');

        DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => $this->createDefaultQueryBuilderDBAL(),
            'simplified_request' => true,
        ]);
    }

    /**
     * @dataProvider getTestCountQueryBuilderORMSimplifiedRequestOptionNotAllowedProvider
     */
    public function testCountQueryBuilderORMSimplifiedRequestOptionNotAllowed(string $behavior): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The "simplified_request" option can only be used when "behavior" option is set to "orm"');

        // @phpstan-ignore-next-line
        DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => $this->createDefaultQueryBuilderORM(),
            'behavior' => $behavior,
            'simplified_request' => true,
            'alias' => ('count_by_alias' === $behavior) ? 'e.entityId' : null,
        ]);
    }

    /**
     * @return array<array{string}>
     */
    public static function getTestCountQueryBuilderORMSimplifiedRequestOptionNotAllowedProvider(): array
    {
        return [
            ['count_by_alias'],
            ['count_by_sub_request'],
        ];
    }

    public function testCountQueryBuilderDBALCountBySubRequestMissingConnectionOption(): void
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('When "behavior" option is set to "count_by_sub_request" with DBAL QueryBuilder, "connection" option is required');

        DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => $this->createDefaultQueryBuilderDBAL(),
            'behavior' => 'count_by_sub_request',
        ]);
    }

    public function testCountQueryBuilderDBALCountBySubRequestBadConnectionOption(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('"connection"');

        // @phpstan-ignore-next-line
        DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => $this->createDefaultQueryBuilderDBAL(),
            'behavior' => 'count_by_sub_request',
            'connection' => [],
        ]);
    }

    public function testCountQueryBuilderORMConnectionOptionNotAllowed(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The "connection" option can only be used with DBAL QueryBuilder');

        DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => $this->createDefaultQueryBuilderORM(),
            'connection' => $this->em->getConnection(),
        ]);
    }

    public function testCountQueryBuilderDBALWithCountByAliasBehaviorWithDistinct(): void
    {
        $queryBuilder = $this->createDefaultQueryBuilderDBAL();
        $this->saveQueryBuilder($queryBuilder);

        $count = DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => $queryBuilder,
            'behavior' => 'count_by_alias',
            'alias' => 'e.entity_id',
            'distinct_alias' => true,
        ]);

        $this->assertSame(52, $count);
        $this->assertSame(1, $this->sqlLogger->currentQuery);
        $this->assertStringContainsStringIgnoringCase('SELECT count(DISTINCT e.entity_id) FROM', $this->sqlLogger->queries[1]['sql']);
        $this->assertStringNotContainsStringIgnoringCase('ORDER', $this->sqlLogger->queries[1]['sql']);
        $this->checkIfQueryBuildNotChange($queryBuilder);
    }

    public function testCountQueryBuilderDBALWithCountByAliasBehaviorWithoutDistinct(): void
    {
        $queryBuilder = $this->createDefaultQueryBuilderDBAL();
        $this->saveQueryBuilder($queryBuilder);

        $count = DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => $queryBuilder,
            'behavior' => 'count_by_alias',
            'alias' => 'e.entity_id',
            'distinct_alias' => false,
        ]);

        $this->assertSame(52, $count);
        $this->assertSame(1, $this->sqlLogger->currentQuery);
        $this->assertStringContainsStringIgnoringCase('SELECT count(e.entity_id) FROM', $this->sqlLogger->queries[1]['sql']);
        $this->assertStringNotContainsStringIgnoringCase('ORDER', $this->sqlLogger->queries[1]['sql']);
        $this->checkIfQueryBuildNotChange($queryBuilder);
    }

    public function testCountQueryBuilderDBALWithCountBySubRequestBehavior(): void
    {
        $queryBuilder = $this->createDefaultQueryBuilderDBAL();
        $this->saveQueryBuilder($queryBuilder);

        $count = DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => $queryBuilder,
            'behavior' => 'count_by_sub_request',
            'connection' => $this->em->getConnection(),
        ]);

        $this->assertSame(52, $count);
        $this->assertSame(1, $this->sqlLogger->currentQuery);
        $this->assertStringContainsStringIgnoringCase('SELECT count(*) FROM', $this->sqlLogger->queries[1]['sql']);
        $this->assertStringContainsStringIgnoringCase('mainquery', $this->sqlLogger->queries[1]['sql']);
        $this->assertStringNotContainsStringIgnoringCase('ORDER', $this->sqlLogger->queries[1]['sql']);
        $this->checkIfQueryBuildNotChange($queryBuilder);
    }

    public function testCountQueryBuilderDBALWithCountBySelectAllBehavior(): void
    {
        $queryBuilder = $this->createDefaultQueryBuilderDBAL();
        $this->saveQueryBuilder($queryBuilder);

        $count = DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => $queryBuilder,
            'behavior' => 'count_by_select_all',
        ]);

        $this->assertSame(52, $count);
        $this->assertSame(1, $this->sqlLogger->currentQuery);
        $this->assertStringContainsStringIgnoringCase('SELECT count(*) FROM', $this->sqlLogger->queries[1]['sql']);
        $this->assertStringNotContainsStringIgnoringCase('mainquery', $this->sqlLogger->queries[1]['sql']);
        $this->assertStringNotContainsStringIgnoringCase('ORDER', $this->sqlLogger->queries[1]['sql']);
        $this->checkIfQueryBuildNotChange($queryBuilder);
    }

    public function testCountQueryBuilderORMWithOrmBehaviorWithSimplifiedRequest(): void
    {
        $queryBuilder = $this->createDefaultQueryBuilderORM();
        $this->saveQueryBuilder($queryBuilder);

        $count = DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => $queryBuilder,
            'behavior' => 'orm',
            'simplified_request' => true,
        ]);

        $this->assertSame(52, $count);
        $this->assertSame(1, $this->sqlLogger->currentQuery);
        $this->assertStringContainsStringIgnoringCase('SELECT count(DISTINCT e0_.entity_id) AS sclr_0 FROM', $this->sqlLogger->queries[1]['sql']);
        $this->assertStringNotContainsStringIgnoringCase('ORDER', $this->sqlLogger->queries[1]['sql']);
        $this->checkIfQueryBuildNotChange($queryBuilder);
    }

    public function testCountQueryBuilderORMWithOrmBehaviorWithoutSimplifiedRequest(): void
    {
        $queryBuilder = $this->createDefaultQueryBuilderORM();
        $this->saveQueryBuilder($queryBuilder);

        $count = DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => $queryBuilder,
            'behavior' => 'orm',
            'simplified_request' => false,
        ]);

        $this->assertSame(52, $count);
        $this->assertSame(1, $this->sqlLogger->currentQuery);
        $this->assertStringContainsStringIgnoringCase('SELECT COUNT(*) AS dctrn_count FROM (', $this->sqlLogger->queries[1]['sql']);
        $this->assertStringContainsStringIgnoringCase('ORDER', $this->sqlLogger->queries[1]['sql']);
        $this->checkIfQueryBuildNotChange($queryBuilder);
    }

    public function testCountQueryBuilderORMWithCountByAliasBehaviorWithDistinct(): void
    {
        $queryBuilder = $this->createDefaultQueryBuilderORM();
        $this->saveQueryBuilder($queryBuilder);

        $count = DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => $queryBuilder,
            'behavior' => 'count_by_alias',
            'alias' => 'e.entityId',
            'distinct_alias' => true,
        ]);

        $this->assertSame(52, $count);
        $this->assertSame(1, $this->sqlLogger->currentQuery);
        $this->assertStringContainsStringIgnoringCase('SELECT count(DISTINCT e0_.entity_id) AS sclr_0 FROM', $this->sqlLogger->queries[1]['sql']);
        $this->assertStringNotContainsStringIgnoringCase('ORDER', $this->sqlLogger->queries[1]['sql']);
        $this->checkIfQueryBuildNotChange($queryBuilder);
    }

    public function testCountQueryBuilderORMWithCountByAliasBehaviorWithoutDistinct(): void
    {
        $queryBuilder = $this->createDefaultQueryBuilderORM();
        $this->saveQueryBuilder($queryBuilder);

        $count = DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => $queryBuilder,
            'behavior' => 'count_by_alias',
            'alias' => 'e.entityId',
            'distinct_alias' => false,
        ]);

        $this->assertSame(52, $count);
        $this->assertSame(1, $this->sqlLogger->currentQuery);
        $this->assertStringContainsStringIgnoringCase('SELECT count(e0_.entity_id) AS sclr_0 FROM', $this->sqlLogger->queries[1]['sql']);
        $this->assertStringNotContainsStringIgnoringCase('ORDER', $this->sqlLogger->queries[1]['sql']);
        $this->checkIfQueryBuildNotChange($queryBuilder);
    }

    public function testCountQueryBuilderORMWithCountBySubRequestBehavior(): void
    {
        $queryBuilder = $this->createDefaultQueryBuilderORM();
        $this->saveQueryBuilder($queryBuilder);

        $count = DoctrinePaginatorBuilder::countQueryBuilder([
            'query_builder' => $queryBuilder,
            'behavior' => 'count_by_sub_request',
        ]);

        $this->assertSame(52, $count);
        $this->assertSame(1, $this->sqlLogger->currentQuery);
        $this->assertStringContainsStringIgnoringCase('SELECT count(*) as cnt FROM', $this->sqlLogger->queries[1]['sql']);
        $this->assertStringContainsStringIgnoringCase('mainquery', $this->sqlLogger->queries[1]['sql']);
        $this->assertStringNotContainsStringIgnoringCase('ORDER', $this->sqlLogger->queries[1]['sql']);
        $this->checkIfQueryBuildNotChange($queryBuilder);
    }

    public function testCreateDoctrinePaginatorDBAL(): void
    {
        $queryBuilder = $this->createDefaultQueryBuilderDBAL();
        $this->saveQueryBuilder($queryBuilder);
        $paginator = DoctrinePaginatorBuilder::createDoctrinePaginator($queryBuilder, 2, 5, [
            'by_identifier' => 'e.entity_id',
        ]);

        $this->assertInstanceOf(DoctrineDBALPaginator::class, $paginator);
        $this->assertSame(2, $paginator->getPage());
        $this->assertSame(5, $paginator->getMaxPerPage());
        $this->assertSame(3, $this->sqlLogger->currentQuery);
        $this->checkIfQueryBuildNotChange($queryBuilder);
    }

    public function testCreateDoctrinePaginatorORM(): void
    {
        $queryBuilder = $this->createDefaultQueryBuilderORM();
        $this->saveQueryBuilder($queryBuilder);
        $paginator = DoctrinePaginatorBuilder::createDoctrinePaginator($queryBuilder, 2, 5, [
            'by_identifier' => 'e.entityId',
        ]);

        $this->assertInstanceOf(DoctrineORMPaginator::class, $paginator);
        $this->assertSame(2, $paginator->getPage());
        $this->assertSame(5, $paginator->getMaxPerPage());
        $this->assertSame(3, $this->sqlLogger->currentQuery);
        $this->checkIfQueryBuildNotChange($queryBuilder);
    }
}
