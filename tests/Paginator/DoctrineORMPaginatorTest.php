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

use Doctrine\ORM\QueryBuilder as QueryBuilderORM;
use Ecommit\DoctrineUtils\Paginator\AbstractDoctrinePaginator;
use Ecommit\DoctrineUtils\Paginator\DoctrineORMPaginator;
use Ecommit\DoctrineUtils\Paginator\DoctrinePaginatorBuilder;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

/**
 * @phpstan-import-type PaginatorOptions from DoctrineORMPaginator
 * @phpstan-import-type CountOptionsWithoutQueryBuilder from DoctrinePaginatorBuilder
 *
 * @template-extends AbstractDoctrinePaginatorTestCase<QueryBuilderORM, DoctrineORMPaginator, mixed, mixed, PaginatorOptions>
 */
class DoctrineORMPaginatorTest extends AbstractDoctrinePaginatorTestCase
{
    /**
     * @param array<string, mixed>                        $options
     * @param int<0, max>|CountOptionsWithoutQueryBuilder $countOptionsExpected
     *
     * @dataProvider getTestCopySimplifiedRequestToCountOptionProvider
     */
    public function testCopySimplifiedRequestToCountOption(array $options, mixed $countOptionsExpected): void
    {
        /** @var PaginatorOptions $options */
        $options = array_merge([
            'page' => 1,
            'max_per_page' => 5,
            'query_builder' => $this->getDefaultQueryBuilder(),
        ], $options);
        $paginator = $this->createPaginator($options);

        $this->assertSame($countOptionsExpected, $paginator->getOption('count'));
    }

    /**
     * @return array<array{array<string, mixed>, int<0, max>|CountOptionsWithoutQueryBuilder}>
     */
    public static function getTestCopySimplifiedRequestToCountOptionProvider(): array
    {
        return [
            [['count' => 8], 8], // With int options
            [[], ['simplified_request' => true]], // With default behavior
            [['count' => ['behavior' => 'orm']], ['behavior' => 'orm', 'simplified_request' => true]],
            [['simplified_request' => true, 'count' => ['behavior' => 'orm']], ['behavior' => 'orm', 'simplified_request' => true]],
            [['simplified_request' => false, 'count' => ['behavior' => 'orm']], ['behavior' => 'orm', 'simplified_request' => false]],
            [['count' => ['behavior' => 'orm', 'simplified_request' => true]], ['behavior' => 'orm', 'simplified_request' => true]],
            [['count' => ['behavior' => 'orm', 'simplified_request' => false]], ['behavior' => 'orm', 'simplified_request' => false]],
            [['simplified_request' => false, 'count' => ['behavior' => 'orm', 'simplified_request' => true]], ['behavior' => 'orm', 'simplified_request' => true]],
            [['simplified_request' => true, 'count' => ['behavior' => 'orm', 'simplified_request' => false]], ['behavior' => 'orm', 'simplified_request' => false]],
        ];
    }

    public function testBadSimplifiedRequestOption(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('"simplified_request"');

        $options = $this->getDefaultOptions();
        $options['simplified_request'] = 'bad';
        $this->createPaginator($options); // @phpstan-ignore-line
    }

    public function testDefaultSimplifiedRequestOption(): void
    {
        $options = $this->getDefaultOptions();
        $options['by_identifier'] = null;
        $options['simplified_request'] = null;
        $paginator = $this->createPaginator($options);

        $this->assertTrue($paginator->getOption('simplified_request'));
    }

    public function testSimplifiedRequestOptionNotAllowed(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The "simplified_request" option can only be used when "by_identifier" option is not set');

        $options = $this->getDefaultOptions();
        $options['by_identifier'] = 'id';
        $options['simplified_request'] = true;
        $this->createPaginator($options);
    }

    public function testBadFetchJoinCollectionOption(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('"fetch_join_collection"');

        $options = $this->getDefaultOptions();
        $options['fetch_join_collection'] = 'bad';
        $this->createPaginator($options); // @phpstan-ignore-line
    }

    public function testDefaultFetchJoinCollectionOption(): void
    {
        $options = $this->getDefaultOptions();
        $options['by_identifier'] = null;
        $options['fetch_join_collection'] = null;
        $paginator = $this->createPaginator($options);

        $this->assertFalse($paginator->getOption('fetch_join_collection'));
    }

    public function testFetchJoinCollectionNotAllowed(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The "fetch_join_collection" option can only be used when "by_identifier" option is not set');

        $options = $this->getDefaultOptions();
        $options['by_identifier'] = 'id';
        $options['fetch_join_collection'] = true;
        $this->createPaginator($options);
    }

    public function testItereatorWithFetchJoinCollectionOption(): void
    {
        $queryBuilder = $this->getDefaultQueryBuilder();
        $queryBuilder->addSelect('r')
            ->leftJoin('e.relations', 'r');
        $this->saveQueryBuilder($queryBuilder);

        $options = $this->getDefaultOptions(1, 5, $queryBuilder);
        $options['by_identifier'] = null;
        $options['fetch_join_collection'] = true;

        $paginator = $this->createPaginator($options);

        $this->assertSame(3, $this->sqlLogger->currentQuery);
        $this->assertCount(52, $paginator);
        $this->checkEntityIds($paginator, range(1, 5));
        $this->checkIfQueryBuildNotChange($queryBuilder);
    }

    public function testItereatorWithoutFetchJoinCollectionOption(): void
    {
        $queryBuilder = $this->getDefaultQueryBuilder();
        $queryBuilder->addSelect('r')
            ->leftJoin('e.relations', 'r');
        $this->saveQueryBuilder($queryBuilder);

        $options = $this->getDefaultOptions(1, 5, $queryBuilder);
        $options['by_identifier'] = null;
        $options['fetch_join_collection'] = false;

        $paginator = $this->createPaginator($options);

        $this->assertSame(2, $this->sqlLogger->currentQuery);
        $this->assertCount(52, $paginator);
        /** @var \ArrayIterator<int|string, object> $iterator */
        $iterator = $paginator->getIterator();
        $this->assertLessThan(5, \count($iterator)); // Bad iterator
        $this->checkIfQueryBuildNotChange($queryBuilder);
    }

    public function testItereatorWithSimplifiedRequestOption(): void
    {
        $queryBuilder = $this->getDefaultQueryBuilder();
        $queryBuilder->addSelect('r')
            ->leftJoin('e.relations', 'r');
        $this->saveQueryBuilder($queryBuilder);

        $options = $this->getDefaultOptions(1, 5, $queryBuilder);
        $options['by_identifier'] = null;
        $options['fetch_join_collection'] = true;
        $options['simplified_request'] = true;

        $paginator = $this->createPaginator($options);

        $this->assertSame(3, $this->sqlLogger->currentQuery);
        $this->assertStringNotContainsStringIgnoringCase('dctrn_result', $this->sqlLogger->queries[2]['sql']);
        $this->assertCount(52, $paginator);
        $this->checkEntityIds($paginator, range(1, 5));
        $this->checkIfQueryBuildNotChange($queryBuilder);
    }

    public function testItereatorWithoutSimplifiedRequestOption(): void
    {
        $queryBuilder = $this->getDefaultQueryBuilder();
        $queryBuilder->addSelect('r')
            ->leftJoin('e.relations', 'r');
        $this->saveQueryBuilder($queryBuilder);

        $options = $this->getDefaultOptions(1, 5, $queryBuilder);
        $options['by_identifier'] = null;
        $options['fetch_join_collection'] = true;
        $options['simplified_request'] = false;

        $paginator = $this->createPaginator($options);

        $this->assertSame(3, $this->sqlLogger->currentQuery);
        $this->assertStringContainsStringIgnoringCase('dctrn_result', $this->sqlLogger->queries[2]['sql']);
        $this->assertCount(52, $paginator);
        $this->checkEntityIds($paginator, range(1, 5));
        $this->checkIfQueryBuildNotChange($queryBuilder);
    }

    public function testWithByIdentifierOption(): void
    {
        $queryBuilder = $this->getDefaultQueryBuilder();
        $this->saveQueryBuilder($queryBuilder);

        $options = $this->getDefaultOptions(2, 5, $queryBuilder);
        $options['by_identifier'] = 'e.entityId';

        $paginator = $this->createPaginator($options);

        $this->assertSame(3, $this->sqlLogger->currentQuery);
        $this->assertCount(5, $this->sqlLogger->queries[3]['params']);
        $this->assertEquals(range(6, 10), \array_slice($this->sqlLogger->queries[3]['params'], 0, 5));
        $this->assertCount(52, $paginator);
        $this->checkEntityIds($paginator, range(6, 10));
        $this->checkIfQueryBuildNotChange($queryBuilder);
    }

    protected function getDefaultQueryBuilder(): mixed
    {
        return $this->createDefaultQueryBuilderORM();
    }

    protected function createPaginator(array $options): AbstractDoctrinePaginator
    {
        return new DoctrineORMPaginator($options);
    }
}
