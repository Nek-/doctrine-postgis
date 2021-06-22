<?php

namespace Jsor\Doctrine\PostGIS\Test;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Jsor\Doctrine\PostGIS\Event\ORMSchemaEventSubscriber;
use Jsor\Doctrine\PostGIS\Functions\Configurator;

abstract class AbstractFunctionalTestCase extends AbstractTestCase
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    private static $_conn;

    /**
     * Array of entity class name to their tables that were created.
     *
     * @var array
     */
    private static $_entityTablesCreated = [];

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $_em;

    /**
     * @var \Doctrine\ORM\Tools\SchemaTool
     */
    private $_schemaTool;

    protected function tearDown(): void
    {
        parent::tearDown();

        if (!self::$_entityTablesCreated) {
            return;
        }

        $em = $this->_getEntityManager();

        $classes = [];
        foreach (self::$_entityTablesCreated as $className => $flag) {
            $classes[] = $em->getClassMetadata($className);
        }

        $this->_getSchemaTool()->dropSchema($classes);

        self::$_entityTablesCreated = [];
    }

    protected function _setUpEntitySchema($classNames)
    {
        $em = $this->_getEntityManager();

        $classes = [];
        foreach ((array) $classNames as $className) {
            if (!isset(self::$_entityTablesCreated[$className])) {
                self::$_entityTablesCreated[$className] = true;
                $classes[] = $em->getClassMetadata($className);
            }
        }

        if ($classes) {
            $this->_getSchemaTool()->dropSchema($classes);
            $this->_getSchemaTool()->createSchema($classes);
        }
    }

    protected function _getDbParams()
    {
        return [
            'driver' => $GLOBALS['db_type'],
            'user' => $GLOBALS['db_username'],
            'password' => $GLOBALS['db_password'],
            'host' => $GLOBALS['db_host'],
            'dbname' => $GLOBALS['db_name'],
            'port' => $GLOBALS['db_port'],
        ];
    }

    protected function _getConnection()
    {
        if (!isset(self::$_conn)) {
            self::$_conn = DriverManager::getConnection($this->_getDbParams(), new Configuration());

            self::$_conn->getEventManager()->addEventSubscriber(new ORMSchemaEventSubscriber());

            Configurator::configure(self::$_conn->getConfiguration());

            if (!Type::hasType('tsvector')) {
                Type::addType('tsvector', 'Doctrine\DBAL\Types\TextType');
            }

            $platform = self::$_conn->getDatabasePlatform();
            $platform->registerDoctrineTypeMapping('tsvector', 'tsvector');
        }

        self::$_conn->executeQuery('CREATE EXTENSION IF NOT EXISTS postgis');

        return self::$_conn;
    }

    protected function _getEntityManager(Configuration $config = null)
    {
        if (null !== $this->_em) {
            return $this->_em;
        }

        $connection = $this->_getConnection();

        if (!$config) {
            $config = $connection->getConfiguration();
        }

        $this->_setupConfiguration($config);

        $em = EntityManager::create($connection, $config);

        return $this->_em = $em;
    }

    protected function _getSchemaTool()
    {
        if (null !== $this->_schemaTool) {
            return $this->_schemaTool;
        }

        return $this->_schemaTool = new SchemaTool($this->_getEntityManager());
    }

    protected function _setupConfiguration(Configuration $config)
    {
        if (method_exists($config, 'setMetadataCache')) {
            $config->setMetadataCache(new ArrayCachePool());
            $config->setQueryCacheImpl(DoctrineProvider::wrap(new ArrayCachePool()));
        } else {
            $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache());
            $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache());
        }
        $config->setProxyDir($GLOBALS['TESTS_TEMP_DIR']);
        $config->setProxyNamespace('Proxy');
        $config->setMetadataDriverImpl($this->_getMappingDriver());

        return $config;
    }

    /**
     * Creates default mapping driver.
     *
     * @return \Doctrine\Common\Persistence\Mapping\Driver\MappingDriver
     */
    protected function _getMappingDriver()
    {
        $reader = new AnnotationReader();
        if (class_exists('\Doctrine\Common\Cache\ArrayCache')) {
            $reader = new CachedReader($reader, new \Doctrine\Common\Cache\ArrayCache());
        } else {
            $reader = new CachedReader($reader, DoctrineProvider::wrap(new ArrayCachePool()));
        }

        return new AnnotationDriver($reader);
    }

    protected function _execFile($fileName)
    {
        return $this->_getConnection()->exec(file_get_contents(__DIR__ . '/fixtures/' . $fileName));
    }
}
