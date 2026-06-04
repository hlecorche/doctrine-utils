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

use Doctrine\DBAL\Query\QueryBuilder as QueryBuilderDBAL;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder as QueryBuilderORM;
use Ecommit\DoctrineUtils\Tests\App\Doctrine;
use Ecommit\DoctrineUtils\Tests\App\Entity\Entity;
use Ecommit\DoctrineUtils\Tests\App\Logging\SqlLogger;
use PHPUnit\Framework\TestCase;

abstract class AbstractTestCase extends TestCase
{
    protected EntityManagerInterface $em;
    protected SqlLogger $sqlLogger;
    protected QueryBuilderDBAL|QueryBuilderORM|null $queryBuilder = null;

    protected function setUp(): void
    {
        $this->em = Doctrine::getEntityManager();
        $this->sqlLogger = Doctrine::getLogger();
    }

    protected function tearDown(): void
    {
        $this->em->clear();
        $this->sqlLogger->reset();
        $this->queryBuilder = null;
    }

    protected function createQueryBuilderDBAL(): QueryBuilderDBAL
    {
        return $this->em->getConnection()->createQueryBuilder();
    }

    protected function createDefaultQueryBuilderDBAL(): QueryBuilderDBAL
    {
        $queryBuilder = $this->createQueryBuilderDBAL();
        $queryBuilder->select('e.*')
            ->from('entity', 'e')
            ->andWhere('e.entity_id <= :id')
            ->setParameter('id', 52)
            ->orderBy('e.entity_id', 'ASC');

        return $queryBuilder;
    }

    /**
     * @param class-string $repository
     */
    protected function createQueryBuilderORM(string $repository, string $alias): QueryBuilderORM
    {
        return $this->em->getRepository($repository)->createQueryBuilder($alias);
    }

    protected function createDefaultQueryBuilderORM(): QueryBuilderORM
    {
        $queryBuilder = $this->createQueryBuilderORM(Entity::class, 'e');
        $queryBuilder->select('e')
            ->andWhere('e.entityId <= :id')
            ->setParameter('id', 52)
            ->orderBy('e.entityId', 'ASC');

        return $queryBuilder;
    }

    /**
     * @param iterable<mixed> $result
     * @param mixed[]         $expectedIds
     */
    protected function checkEntityIds(iterable $result, array $expectedIds): void
    {
        $ids = [];
        foreach ($result as $entity) {
            if ($entity instanceof Entity) {
                $ids[] = $entity->getEntityId();
            } elseif (\is_array($entity)) {
                $ids[] = $entity['entity_id'];
            } else {
                throw new \Exception('Non géré');
            }
        }

        $this->assertEquals($expectedIds, $ids);
    }

    protected function saveQueryBuilder(QueryBuilderDBAL|QueryBuilderORM $queryBuilder): void
    {
        $this->queryBuilder = clone $queryBuilder;
    }

    protected function checkIfQueryBuildNotChange(QueryBuilderDBAL|QueryBuilderORM $queryBuilder): void
    {
        $this->assertEquals($this->queryBuilder, $queryBuilder);
    }
}
