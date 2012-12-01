<?php
namespace BrsZfSlothTest\Repository;

use Zend\Db\Adapter\Adapter as DbAdapter;

use BrsZfSloth\Sloth;
use BrsZfSloth\Definition\Definition;
use BrsZfSloth\Repository\Repository;
use BrsZfSloth\Repository\RepositoryOptions;
use BrsZfSloth\Expr;

/**
 * @group BrsZfSloth
 */
class RepositoryTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Sloth::reset();
        Definition::reset();
    }

    /**
     * @expectedException BrsZfSloth\Exception\RuntimeException
     * @expectedExceptionMessage definition name not defined in BrsZfSloth\Repository\Repository
     */
    public function testUdefinedDefinitionName()
    {
        new Repository;
    }

    public function testCreateFromOptions()
    {
        $options = $this->getMock('BrsZfSloth\Repository\RepositoryOptions');
        $options
            ->expects($this->exactly(2))
            ->method('getDefinition')
            ->will($this->returnValue(
                $this->getMockDefinition()
            ))
        ;
        $options
            ->expects($this->exactly(1))
            ->method('getCache')
            ->will($this->returnValue(
                $this->getMockDataCache()
            ))
        ;
        $options
            ->expects($this->exactly(1))
            ->method('getDbAdapter')
            ->will($this->returnValue(
                $this->getMockDbAdapter()
            ))
        ;
        $options
            ->expects($this->exactly(1))
            ->method('getEventManager')
            ->will($this->returnValue(
                $this->getMockEventManager()
            ))
        ;

        $r = new Repository($options);

        $this->assertInstanceOf('BrsZfSloth\Definition\Definition', $r->getDefinition());
        $this->assertInstanceOf('BrsZfSloth\Repository\Cache', $r->getCache());
        $this->assertInstanceOf('Zend\Db\Adapter\Adapter', $r->getAdapter());
        $this->assertInstanceOf('Zend\EventManager\EventManager', $r->getEventManager());
    }

    public function testCreateEntity()
    {
        $entityProp = ['id' => 123];

        $options = $this->getMockOptions();

        $options->getDefinition()->getOptions()
            ->expects($this->exactly(1))
            ->method('getEntityClass')
            ->will($this->returnValue($entityClass = 'BrsZfSlothTest\Repository\TestAsset\TestEntity'))
        ;
        $options->getDefinition()
            ->expects($this->exactly(1))
            ->method('getHydrator')
            ->will($this->returnValue($this->getMock('Zend\Stdlib\Hydrator\AbstractHydrator')))
        ;
        $options->getDefinition()
            ->expects($this->exactly(1))
            ->method('assertEntityClass')
            ->will($this->returnValue($options->getDefinition()))
        ;
// mprd($options->getDefinition()->getHydrator());
        // // hydrator
        // $hydrator = $this->getMock('Zend\Stdlib\Hydrator\HydratorInterface');
        // $hydrator
        //     ->expects($this->exactly(1))
        //     ->method('hydrate')
        //     ->with($this->equalTo($entityProp), $this->isInstanceOf($entityClass))
        // ;
        // $options
        //     ->getDefinition()
        //         ->getOptions()
        //             ->expects($this->exactly(1))
        //             ->method('getHydrator')
        //             ->will($this->returnValue($hydrator))
        // ;

        $r = new Repository($options);
        $entity = $r->createEntity($entityProp);
        $this->assertInstanceOf($entityClass, $entity);
    }

    // public function testInsert()
    // {
    //     // $options = $this->getMock('BrsZfSloth\Repository\RepositoryOptions');

    //     // // XXX difficult make mock definition
    //     // $options
    //     //     ->expects($this->any())
    //     //     ->method('getDefinition')
    //     //     ->will($this->returnValue(new Definition([
    //     //         'name' => 'testdef',
    //     //         'table' => 'test',
    //     //         'fields' => [
    //     //             'id' => 'integer',
    //     //             'name' => 'text',
    //     //         ]
    //     //     ])))
    //     // ;

    //     $dbAdapter = $this->getMockDbAdapter();
    //     $dbAdapter->expects($this->exactly(1))->method('createStatement')->will($this->returnValue(
    //         $this->getMock('Zend\Db\Adapter\StatementContainerInterface')
    //     ));
    //     $dbAdapter->expects($this->any())->method('getPlatform')->will($this->returnValue(
    //         $this->getMock('Zend\Db\Adapter\Platform\PlatformInterface')
    //     ));
    //     // mprd($dbAdapter->getPlatform());

    //     $r = new Repository([
    //         'dbAdapter' => $dbAdapter,
    //         'definition' => [
    //             'name' => 'testdef',
    //             'table' => 'test',
    //             'fields' => [
    //                 'id' => 'integer',
    //                 'name' => 'text',
    //             ]
    //         ]
    //     ]);
    //     // mprd($r);
    //     $id = $r->insert((object) ['id' => 123, 'name' => 'test']);
    //     mprd($id);

    //     // $this->assertInstanceOf($entityClass, $entity);

    // }

    public function testCreateCollection()
    {
        $options = $this->getMockOptions();
        $options
            ->getDefinition()
                ->getOptions()
                    ->expects($this->exactly(1))
                    ->method('getCollectionClass')
                    ->will($this->returnValue($collectionClass = get_class($this->getMock('ArrayAccess'))))
        ;
        $r = new Repository($options);
        $collection = $r->createCollection();
        $this->assertInstanceOf($collectionClass, $collection);
    }

    protected function getMockOptions()
    {
        $options = $this->getMock('BrsZfSloth\Repository\RepositoryOptions');
        $options
            ->expects($this->any())
            ->method('getDefinition')
            ->will($this->returnValue(
                $this->getMockDefinition()
            ))
        ;
        $options
            ->expects($this->any())
            ->method('getCache')
            ->will($this->returnValue(
                $this->getMockDataCache()
            ))
        ;
        $options
            ->expects($this->any())
            ->method('getDbAdapter')
            ->will($this->returnValue(
                $this->getMockDbAdapter()
            ))
        ;
        $options
            ->expects($this->any())
            ->method('getEventManager')
            ->will($this->returnValue(
                $this->getMockEventManager()
            ))
        ;
        return $options;
    }

    protected function getMockDefinition(array $config = array(
        'name' => 'test_name',
        'table' => 'test_table'
    ))
    {
        $options = $this->getMock('BrsZfSloth\Definition\DefinitionOptions');
        // $options
        //     ->expects($this->any())
        //     ->method('getHydrator')
        //     ->will($this->returnValue(
        //         $this->getMock('BrsZfSloth\Hydrator\Hydrator')
        //     ))
        // ;
        $def = $this->getMock(
            'BrsZfSloth\Definition\Definition',
            array(),
            array($config),
            '',
            false
        );

        $def
            ->expects($this->any())
            ->method('getOptions')
            ->will($this->returnValue($options))
        ;
        // mprd($def->getOptions()->getHydrator());
        return $def;
    }

    protected function getMockEventManager()
    {
        $em = $this->getMock('Zend\EventManager\EventManager');
        return $em;
    }

    protected function getMockDataCache($namespace = 'test:dsn||BrsZfSloth\Repository\Repository', $namespaceSeparator = '||')
    {
        $cache = $this->getMock('Zend\Cache\Storage\StorageInterface');

        $cacheOptions = $this->getMock('Zend\Cache\Storage\Adapter\AdapterOptions', ['setNamespace', 'getNamespace', 'getNamespaceSeparator']);
        $cacheOptions
            ->expects($this->exactly(1))
            ->method('setNamespace')
            ->with($this->equalTo($namespace))
        ;
        $cacheOptions
            ->expects($this->any())
            ->method('getNamespace')
            ->will($this->returnValue($namespace))
        ;
        $cacheOptions
            ->expects($this->any())
            ->method('getNamespaceSeparator')
            ->will($this->returnValue($namespaceSeparator))
        ;
        $cache
            ->expects($this->any())
            ->method('getOptions')
            ->will($this->returnValue($cacheOptions))
        ;

        // mprd($cache->getOptions()->getNamespaceSeparator());
        return $cache;
    }

    protected function getMockDbAdapter($dsn = 'test:dsn')
    {
        $adapter = $this->getMock('Zend\Db\Adapter\Adapter', [], [], '', false);

        // $conn = $this->getMock('Zend\Db\Adapter\Driver\ConnectionInterface');
        $conn = $this->getMock('Zend\Db\Adapter\Driver\Pdo\Connection');
        $conn
            ->expects($this->any())
            ->method('getConnectionParameters')
            ->will($this->returnValue([
                'dsn' => $dsn
            ]))
        ;

        $driver = $this->getMock('Zend\Db\Adapter\Driver\DriverInterface');
        $driver
            ->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($conn))
        ;
        $adapter
            ->expects($this->any())
            ->method('getDriver')
            ->will($this->returnValue($driver))
        ;

        // mprd($options->getDbAdapter()->getDriver()->getConnection()->getConnectionParameters());
        return $adapter;
    }
}