<?php

namespace BrsZfSlothTest\Generator;

use StdClass;
use DirectoryIterator;

use Zend\Db\Sql as ZfSql;
use Zend\Db\Sql\Predicate\PredicateInterface;
use Zend\Db\Adapter\Adapter;

use BrsZfSloth\Sloth;
use BrsZfSloth\Expr;
use BrsZfSloth\Exception;
// use BrsZfSloth\Sql\Order;
// use BrsZfSloth\Sql\Where;
// use BrsZfSloth\Entity\Entity;
// use BrsZfSloth\Entity\EntityTools;
// use BrsZfSloth\Repository\Repository;
use BrsZfSloth\Definition\Definition;
// use BrsZfSloth\Generator\Descriptor\Pgsql as Descriptor;
// use BrsZfSloth\Definition\DefinitionAwareInterface;


abstract class AbstractIntegrationTest extends \PHPUnit_Framework_TestCase
{
    protected $variables = array(
        'hostname' => 'SLOTH_INTEGRATION_HOSTNAME',
        'dbname'   => 'SLOTH_INTEGRATION_DBNAME',
        'username' => 'SLOTH_INTEGRATION_USERNAME',
        'password' => 'SLOTH_INTEGRATION_PASSWORD',
    );

    protected $adapter;
    protected $savepath;
    protected $testTableName = 'testtable';

    abstract protected function setupTestTable();
    abstract protected function dropTestTable();
    abstract protected function getDescriptor(Adapter $adapter);

    protected function setUp()
    {
        foreach ($this->variables as $name => $value) {
            if (! isset($GLOBALS[$value])) {
                $this->fail('Missing required variable ' . $value . ' from phpunit.xml for this integration test');
            }
            $this->variables[$name] = $GLOBALS[$value];
        }

        $this->savepath = __DIR__ . '/TestAsset/savepath';

        Sloth::reset();
        Definition::reset();

        // clean direcotry
        foreach (new DirectoryIterator($this->savepath) as $fileinfo) {
            if ($fileinfo->isFile()) {
                unlink($fileinfo->getPathName());
            }
        }
    }

    public function tearDown()
    {
        $this->dropTestTable();
        $this->adapter = null;
    }

    public function testDescribeDb()
    {
        $this->setupTestTable();

        $descriptor = $this->getDescriptor($this->adapter);
        $dbData = $descriptor->describeDatabase();
        mprd($dbData);


        $this->assertEquals($this->testTableName, $dConfig['name']);
        $this->assertEquals($this->testTableName, $dConfig['table']);
        $this->assertEquals(5, count($dConfig['fields']));
    }

    public function testDescribeTable()
    {
        $this->setupTestTable();

        $descriptor = $this->getDescriptor($this->adapter);
        $tableData = $descriptor->describeTable($this->testTableName);

        $this->assertEquals('id', $tableData['fields'][0]->getName());
        $this->assertEquals('integer', $tableData['fields'][0]->getType());
        $this->assertEquals('id_user', $tableData['fields'][0]->getMapping());
        $this->assertTrue($tableData['fields'][0]->isPrimary());
        $this->assertEquals(5, count($tableData['fields']));
    }
}
