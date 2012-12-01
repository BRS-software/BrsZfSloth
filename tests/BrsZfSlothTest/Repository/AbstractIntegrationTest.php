<?php

namespace BrsZfSlothTest\Repository;

use StdClass;

use Zend\Db\Sql;
use Zend\Db\Sql\Predicate\PredicateInterface;

use BrsZfSloth\Sloth;
use BrsZfSloth\Expr;
use BrsZfSloth\Exception;
use BrsZfSloth\Sql\Order;
use BrsZfSloth\Sql\Where;
use BrsZfSloth\Entity\Entity;
use BrsZfSloth\Entity\EntityTools;
use BrsZfSloth\Repository\Repository;
use BrsZfSloth\Definition\Definition;
use BrsZfSloth\Definition\DefinitionAwareInterface;


abstract class AbstractIntegrationTest extends \PHPUnit_Framework_TestCase
{
    protected $variables = array(
        'hostname' => 'SLOTH_INTEGRATION_HOSTNAME',
        'dbname'   => 'SLOTH_INTEGRATION_DBNAME',
        'username' => 'SLOTH_INTEGRATION_USERNAME',
        'password' => 'SLOTH_INTEGRATION_PASSWORD',
    );

    protected $adapter;
    protected $testTableName = 'testtable';

    abstract protected function setupTestTable();
    abstract protected function dropTestTable();

    protected function setUp()
    {
        Sloth::reset();
        Definition::reset();

        foreach ($this->variables as $name => $value) {
            if (!isset($GLOBALS[$value])) {
                $this->fail('Missing required variable ' . $value . ' from phpunit.xml for this integration test');
            }
            $this->variables[$name] = $GLOBALS[$value];
        }
    }

    public function tearDown()
    {
        $this->dropTestTable();
        $this->adapter = null;
    }

    // static because used in data provider
    protected function getTestDefinition()
    {
        return new Definition([
            'name' => 'users',
            'table' => $this->testTableName,
            'entityClass' => 'StdClass',
            'hydratorClass' => 'Zend\Stdlib\Hydrator\ObjectProperty',
            'fields' => [
                'id' => [
                    'type' => 'integer',
                    'mapping' => 'id_user',
                    'primary' => true,
                ],
                'crtDate' => [
                    'type' => 'datetime',
                    'default' => date('Y-m-d H:i:s'),
                    'notNull' => true,
                ],
                'nick' => [
                    'type' => 'character varying(16)',
                    'mapping' => 'short_name',
                    'notNull' => true,
                ],
                'isActive' => [
                    'type' => 'boolean',
                    'default' => false,
                    'notNull' => true,
                ],
                'comment' => 'text',
            ]
        ]);
    }

    public static function entityProvider()
    {
        return [
            // Sloth entity with sloth hydrator
            [
                new Entity,
                new \BrsZfSloth\Hydrator\Hydrator,
            ],
            // The class doesn't inherit from nothing
            [
                new TestAsset\TestEntityUserMethods,
                new \Zend\Stdlib\Hydrator\ClassMethods(false),
            ],
            // StdClass entity with properties
            [
                new StdClass,
                new \Zend\Stdlib\Hydrator\ObjectProperty,
            ],
        ];
    }


    protected function insertTestData(array $data = null)
    {
        // insert standard data into table
        if (null === $data) {
            $this->insertTestData([
                'short_name' => 'tester1',
                'comment' => 'standard data row 1'
            ]);
            $this->insertTestData([
                'short_name' => 'tester2',
                'comment' => 'standard data row 2'
            ]);
            $this->insertTestData([
                'short_name' => 'tester3',
                'comment' => 'standard data row 3'
            ]);
            return;
        }

        $statment = $this->adapter->createStatement();

        $insert = (new Sql\Insert($this->testTableName))
            ->values($data)
            ->prepareStatement($this->adapter, $statment)
        ;
        $affected = $statment->execute()->getAffectedRows();
        // mprd($affected);
        // try {
        // } catch (DbException $e) {
        //     throw new Exception\StatementException($update->getSqlString(), 0, $e);
        // }
    }

    public function testGet()
    {
        $this->setupTestTable();
        $this->insertTestData();

        $repo = new Repository([
            'dbAdapter' => $this->adapter,
            'definition' => $this->getTestDefinition(),
        ]);

        $this->assertEquals(1, $repo->get('nick', 'tester1')->id);
        $this->assertEquals(1, $repo->get("{nick}='tester1'")->id);
        $this->assertEquals(1, $repo->get(['nick' => 'tester1'])->id);
        $this->assertEquals(1, $repo->get(new Where("{nick}='tester1'"))->id);
        $this->assertEquals(1, $repo->get(function(\Zend\Db\Sql\Select $select) {
            // $select->where("short_name='tester1'");
            $select->where(new Where("{nick}='tester1'"));
        })->id);
    }

    public function testEnableDisableCache()
    {
        $this->setupTestTable();
        $this->insertTestData();

        $repo = new Repository([
            'dbAdapter' => $this->adapter,
            'definition' => $this->getTestDefinition(),
            'caching' => true,
        ]);
        $repo->getCache()->clearStorage();

        $queryCounter = 0;
        $repo->getEventManager()->attach('pre.select', function($e) use (&$queryCounter) {
            // mprd($e->getParam('select')->getSqlString());
            $queryCounter++;
        });

        $repo->get('nick', 'tester1');
        $repo->get("{nick}='tester1'");
        $this->assertEquals(1, $queryCounter);

        $repo->getCache()->getStorage()->setCaching(false);
        $repo->get('nick', 'tester1');
        $repo->get("{nick}='tester1'");
        $this->assertEquals(3, $queryCounter);
    }

    public function testClearCacheOnUpdate()
    {
        $this->setupTestTable();
        $this->insertTestData();

        $repo = new Repository([
            'dbAdapter' => $this->adapter,
            'definition' => $this->getTestDefinition(),
            'caching' => true,
        ]);
        $repo->getCache()->clearStorage();

        $queryCounter = 0;
        $repo->getEventManager()->attach('pre.select', function($e) use (&$queryCounter) {
            $queryCounter++;
        });

        $entity = $repo->get('nick', 'tester1'); // first query to db (create cache for select where nick=tester1)
        $repo->get('nick', 'tester2'); // second query to db
        $entity->nick = 'xxx';
        $repo->update($entity); // update tester1 must clear cache for entire repository
        $repo->get('nick', 'tester2'); // must get data from db, not from cache

        $this->assertEquals(3, $queryCounter);
    }

    public function testClearCacheOnDelete()
    {
        $this->setupTestTable();
        $this->insertTestData();

        $repo = new Repository([
            'dbAdapter' => $this->adapter,
            'definition' => $this->getTestDefinition(),
            'caching' => true,
        ]);
        $repo->getCache()->clearStorage();

        $queryCounter = 0;
        $repo->getEventManager()->attach('pre.select', function($e) use (&$queryCounter) {
            $queryCounter++;
        });

        $entity = $repo->get('nick', 'tester1'); // first query to db (create cache for select where nick=tester1)
        $repo->get('nick', 'tester2'); // second query to db
        $repo->delete($entity); // delete tester1 must clear cache for entire repository
        $repo->get('nick', 'tester2'); // must get data from db, not from cache

        $this->assertEquals(3, $queryCounter);
    }

    /**
     * @expectedException BrsZfSloth\Exception\NotFoundException
     */
    public function testGetNotFound()
    {
        $this->setupTestTable();
        $this->insertTestData();

        $repo = new Repository([
            'dbAdapter' => $this->adapter,
            'definition' => $this->getTestDefinition(),
        ]);

        $repo->get('nick', 'xxx');
    }

    /**
     * @expectedException BrsZfSloth\Exception\AmbiguousException
     */
    public function testGetAmbiguous()
    {
        $this->setupTestTable();
        $this->insertTestData();

        $repo = new Repository([
            'dbAdapter' => $this->adapter,
            'definition' => $this->getTestDefinition(),
        ]);

        $repo->get(function() {});
    }

    public function testGetByMethod()
    {
        $this->setupTestTable();
        $this->insertTestData();

        $repo = new TestAsset\TestRepository([
            'dbAdapter' => $this->adapter,
            'definition' => $this->getTestDefinition(),
        ]);

        $this->assertEquals('xyz', $repo->getByMethod('testGetMethod', 'xyz')->comment);
    }

    /**
     * @expectedException BrsZfSloth\Exception\BadMethodCallException
     * $expectedExcetpionMessage method BrsZfSloth\Repository\Repository::notExistingMethod() does not exist
     */
    public function testGetByMethodFail()
    {
        $repo = new Repository([
            'dbAdapter' => $this->adapter,
            'definition' => $this->getTestDefinition(),
        ]);

        $repo->getByMethod('notExistingMethod');
    }

    public function testFetch()
    {
        $this->setupTestTable();
        $this->insertTestData();

        $repo = new Repository([
            'dbAdapter' => $this->adapter,
            'definition' => $this->getTestDefinition(),
        ]);

        $this->assertEquals(1, $repo->fetch('nick', 'tester1')[0]->id);
        $this->assertEquals(1, $repo->fetch("{nick}='tester1'")[0]->id);
        $this->assertEquals(1, $repo->fetch(['nick' => 'tester1'])[0]->id);
        $this->assertEquals(1, $repo->fetch(new Where("{nick}='tester1'"))[0]->id);
        $this->assertEquals(1, $repo->fetch(function(\Zend\Db\Sql\Select $select) {
            $select->where(new Where("{nick}='tester1'"));
        })[0]->id);

        // fetch all
        $this->assertEquals(3, $repo->fetch()->count());
        // fetch with order
        $this->assertEquals(3, $repo->fetch(function(\Zend\Db\Sql\Select $select) {
            $select->order(new Order('nick', SORT_DESC));
        })[0]->id);
        // fetch with limit
        $this->assertEquals(2, $repo->fetch(function(\Zend\Db\Sql\Select $select) {
            $select->limit(2);
        })->count());

    }


    /**
     * @dataProvider entityProvider
     */
    public function testInsert($entity, $hydrator)
    {
        $def = $this->getTestDefinition();
        $def->getOptions()->setEntityClass(get_class($entity));
        $def->setHydrator($hydrator);

        if ($entity instanceof DefinitionAwareInterface) {
            $entity->setDefinition($def); // TODO hmm maybe can do this in Sloth hydrator
        }

        EntityTools::populate([
            'nick' => 'xxx',
            'comment' => 'test for entity class '.get_class($entity)
        ], $entity, $def);

        $this->setupTestTable();
        $repo = new Repository([
            'dbAdapter' => $this->adapter,
            'definition' => $def,
        ]);
        $lastId = $repo->insert($entity);
        $this->assertEquals(1, $lastId);
    }

    /**
     * @dataProvider entityProvider
     */
    public function testUpdate($entity, $hydrator)
    {
        $def = $this->getTestDefinition();
        $def->getOptions()->setEntityClass(get_class($entity));
        $def->setHydrator($hydrator);

        if ($entity instanceof DefinitionAwareInterface) {
            $entity->setDefinition($def); // TODO hmm maybe can do this in Sloth hydrator
        }

        $this->setupTestTable();
        $this->insertTestData();

        $repo = new Repository([
            'dbAdapter' => $this->adapter,
            'definition' => $def,
        ]);

        $entity = $repo->get('nick', 'tester1');
        EntityTools::setValue('isActive', true, $entity, $def);
        EntityTools::setValue('comment', 'yyy', $entity, $def);
        $affected = $repo->update($entity);
        $this->assertEquals(
            EntityTools::getValue('isActive', $entity, $def),
            EntityTools::getValue(
                'isActive',
                $repo->get('id', EntityTools::getValue('id', $entity, $def)),
                $def
            )
        );
    }

    /**
     * @dataProvider entityProvider
     */
    public function testDelete($entity, $hydrator)
    {
        $def = $this->getTestDefinition();
        $def->getOptions()->setEntityClass(get_class($entity));
        $def->setHydrator($hydrator);

        if ($entity instanceof DefinitionAwareInterface) {
            $entity->setDefinition($def); // TODO hmm maybe can do this in Sloth hydrator
        }

        $this->setupTestTable();
        $this->insertTestData();

        $repo = new Repository([
            'dbAdapter' => $this->adapter,
            'definition' => $def,
        ]);

        $entity = $repo->get('id', 1);
        $repo->delete($entity);

        $this->assertEquals(2, $repo->fetch()->count());

    }

    /**
     * @group disable
     */
    public function testSpeed()
    {
        $this->setupTestTable();
        $this->insertTestData();

        // Sloth::configure([
        //     'data_caching' => true
        // ]);
        $repo = new Repository([
            'dbAdapter' => $this->adapter,
            'definition' => $this->getTestDefinition(),
            'caching' => 1,
        ]);

        // $c = $repo->fetch('nic')
        for ($i=0; $i < 10000; $i++) {
            $this->assertEquals(1, $repo->fetch('nick', 'tester1')[0]->id);
        }
    }

}
