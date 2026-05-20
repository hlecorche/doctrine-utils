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

namespace Ecommit\DoctrineUtils\Tests\App;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Ecommit\DoctrineUtils\Tests\App\Entity\Entity;
use Ecommit\DoctrineUtils\Tests\App\Entity\Relation;
use Ecommit\DoctrineUtils\Tests\App\Logging\Middleware;
use Ecommit\DoctrineUtils\Tests\App\Logging\SqlLogger;

class Doctrine
{
    protected static ?EntityManagerInterface $entityManager = null;
    protected static ?SqlLogger $sqlLogger = null;

    public static function getEntityManager(): EntityManagerInterface
    {
        if (static::$entityManager) {
            return static::$entityManager;
        }

        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/Entity'], true);
        $config->setMiddlewares([new Middleware(static::getLogger())]);
        if (\PHP_VERSION_ID >= 80400 && method_exists($config, 'enableNativeLazyObjects')) {
            $config->enableNativeLazyObjects(true);
        }

        $connection = DriverManager::getConnection(
            [
                'driver' => 'pdo_sqlite',
                'memory' => true,
            ],
            $config
        );

        static::$entityManager = new EntityManager($connection, $config);

        return static::$entityManager;
    }

    public static function getLogger(): SqlLogger
    {
        if (static::$sqlLogger) {
            return static::$sqlLogger;
        }

        static::$sqlLogger = new SqlLogger();

        return static::$sqlLogger;
    }

    public static function createSchema(): void
    {
        $entityManager = self::getEntityManager();

        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->createSchema(
            $entityManager->getMetadataFactory()->getAllMetadata()
        );
    }

    public static function loadFixtures(): void
    {
        $em = static::getEntityManager();

        $relationId = 0;
        for ($entityId = 1; $entityId <= 62; ++$entityId) {
            $entity = new Entity();
            $entity->setEntityId($entityId);
            $entity->setTitle('Entity '.$entityId);
            $em->persist($entity);

            for ($i = 0; $i <= $entityId % 3; ++$i) {
                ++$relationId;
                $relation = new Relation();
                $relation->setRelationId($relationId);
                $relation->setTitle('Relation '.$relationId);
                $entity->addRelation($relation);
                $em->persist($relation);
            }
        }

        $em->flush();
        $em->clear();

        static::getLogger()->reset();
    }
}
