<?php
namespace BrsZfSlothTest\Getnerator;

use DirectoryIterator;
// use Zend\Db\Adapter\Adapter as DbAdapter;

use BrsZfSloth\Sloth;
use BrsZfSloth\Definition\Definition;
use BrsZfSloth\Generator\DefinitionGenerator;
// use BrsZfSloth\Repository\Repository;
// use BrsZfSloth\Repository\RepositoryOptions;
// use BrsZfSloth\Expr;

/**
 * @group BrsZfSloth
 */
class DefinitionGeneratorTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->savepath = __DIR__ . '/TestAsset/savepath';

        Sloth::reset();
        Definition::reset();
        $this->cleanSavePath();
    }

    protected function tearDown()
    {
        // $this->cleanSavePath();
    }

    protected function cleanSavePath()
    {
        // clean direcotry
        foreach (new DirectoryIterator($this->savepath) as $fileinfo) {
            if ($fileinfo->isFile()) {
                unlink($fileinfo->getPathName());
            }
        }
    }

    public function testSaveDefinitionConfig()
    {
        $gen = new DefinitionGenerator([
            'db_adapter' => $this->getMockDbAdapter(),
            'save_path' => $this->savepath,
            // 'descriptor' => $this-getMockDescriptor(),
        ]);

        $file = $gen->saveDefinition([
            'name' => $defName = 'testname',
            'table' => 'testtable',
        ]);
        // mprd($file);
        $defConfig = Definition::discoverConfig($defName, $this->savepath);
        $def = new Definition($defConfig);

        $this->assertEquals($file, $def->getOriginFile());
        $this->assertEquals($defName, $def->getName());
    }

    /**
     * @expectedException BrsZfSloth\Exception\OperationNotPermittedException
     */
    public function testSaveDefinitionConfigWhenConfigExistsFail()
    {
        $gen = new DefinitionGenerator([
            'db_adapter' => $this->getMockDbAdapter(),
            'save_path' => $this->savepath,
            'allow_replace_existing_config' => false,
        ]);

        $gen->saveDefinition([
            'name' => $defName = 'testname',
            'table' => 'testtable',
        ]);
        // second save must thrown exception
        $gen->saveDefinition([
            'name' => $defName,
            'table' => 'testtable',
        ]);
    }

    public function testSaveDefinitionReplaceExistingConfigTrue()
    {
        $gen = new DefinitionGenerator([
            'db_adapter' => $this->getMockDbAdapter(),
            'save_path' => $this->savepath,
            'allow_replace_existing_config' => true,
        ]);

        $gen->saveDefinition([
            'name' => $defName = 'testname',
            'table' => 'testtable',
            'fields' => [
                'id' => 'integer'
            ]
        ]);
        $gen->saveDefinition([
            'name' => $defName,
            'table' => 'testtable',
            'fields' => [
                'id' => 'text',
                'name' => 'text',
            ]
        ]);
        $conf = Definition::discoverConfig($defName, $this->savepath);
        // mprd($conf);
        $this->assertEquals(2, count($conf['fields']));
        $this->assertEquals('integer', $conf['fields'][0]['type']);
        $this->assertEquals('text', $conf['fields'][1]['type']);
    }

    public function testSaveDefinitionReplaceExistingConfigTrueAndRebuildFields()
    {
        $gen = new DefinitionGenerator([
            'db_adapter' => $this->getMockDbAdapter(),
            'save_path' => $this->savepath,
            'allow_replace_existing_config' => true,
            'rebuild_fields_existing_config' => true,
        ]);

        $gen->saveDefinition([
            'name' => $defName = 'testname',
            'table' => 'testtable',
            'fields' => [
                'id' => 'integer'
            ]
        ]);
        $gen->saveDefinition([
            'name' => $defName,
            'table' => 'testtable',
            'fields' => [
                'id' => 'text',
                'name' => 'text',
            ]
        ]);
        $conf = Definition::discoverConfig($defName, $this->savepath);
        // mprd($conf);
        $this->assertEquals(2, count($conf['fields']));
        $this->assertEquals('text', $conf['fields'][0]['type']);
        $this->assertEquals('text', $conf['fields'][1]['type']);
    }

    public function testSaveDefinitionReplaceExistingConfigTrueAndFullRebuild()
    {
        $gen = new DefinitionGenerator([
            'db_adapter' => $this->getMockDbAdapter(),
            'save_path' => $this->savepath,
            'allow_replace_existing_config' => true,
            'full_rebuild_existing_config' => true,
        ]);

        $gen->saveDefinition([
            'name' => $defName = 'testname',
            'table' => 'testtable',
            'fields' => [
                'id' => 'integer'
            ]
        ]);
        $gen->saveDefinition([
            'name' => $defName,
            'table' => 'testtable2',
            'fields' => [
                'id' => 'text',
                'name' => 'text',
            ]
        ]);
        $conf = Definition::discoverConfig($defName, $this->savepath);
        // mprd($conf);
        $this->assertEquals('testtable2', $conf['table']);
        $this->assertEquals(2, count($conf['fields']));
        $this->assertEquals('text', $conf['fields'][0]['type']);
        $this->assertEquals('text', $conf['fields'][1]['type']);
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