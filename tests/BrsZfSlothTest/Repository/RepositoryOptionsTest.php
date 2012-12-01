<?php

namespace BrsZfSlothTest\Repository;

use BrsZfSloth\Sloth;
use BrsZfSloth\Options as DefaultOptions;
use BrsZfSloth\Repository\RepositoryOptions;

// use Zend\Db\Adapter\Adapter;

// class Test
// {
//     public function fuckup()
//     {
//         return new $this->getClassName();
//     }

//     public function getClassName()
//     {
//         return 'StdClass';
//     }
// }

// (new Test)->fuckup();
// exit;

/**
 * @group BrsZfSloth
 */
class RepositoryOptionsTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Sloth::reset();
    }

    public function testGetDefaultOptions()
    {
        $defaultOptions = new DefaultOptions([
            'default_db_adapter' => $this->getMock('Zend\Db\Adapter\Adapter', [], [], '', false),
            'default_event_manager_class' => 'BrsZfSlothTest\Repository\TestAsset\TestEventManager',
        ]);

        $options = new RepositoryOptions([], $defaultOptions);


        $this->assertSame($defaultOptions->getDefaultDbAdapter(), $options->getDbAdapter());
        $this->assertSame($defaultOptions->getDefaultEventManagerClass(), $options->getEventManagerClass());
        $this->assertInstanceof($defaultOptions->getDefaultEventManagerClass(), $options->getEventManager());
        $this->assertInstanceof('Zend\Cache\Storage\StorageInterface', $options->getCache());
        $this->assertNull($options->getDefinition());
    }

    public function testGetOptions()
    {
        $defaultOptions = new DefaultOptions([
            'default_db_adapter' => $this->getMock('Zend\Db\Adapter\Adapter', [], [], '', false),
            // 'default_event_manager_class' => 'BrsZfSlothTest\Repository\TestAsset\TestEventManager',
        ]);

        // Sloth::configure($defaultOptions);
        $options = new RepositoryOptions([
            'db_adapter' => $this->getMock('Zend\Db\Adapter\Adapter', [], [], '', false),
            'event_manager_class' => 'BrsZfSlothTest\Repository\TestAsset\TestEventManager',
            'definition' => 'testdef',
        ], $defaultOptions);
        // mprd($options->getDataCache());

        $this->assertNotSame($defaultOptions->getDefaultDbAdapter(), $options->getDbAdapter());
        $this->assertNotSame($defaultOptions->getDefaultEventManagerClass(), $options->getEventManagerClass());
        $this->assertInstanceof($defaultOptions->getDefaultEventManagerClass(), $options->getEventManager());
        $this->assertInstanceof('Zend\Cache\Storage\StorageInterface', $options->getCache());
        $this->assertEquals('testdef', $options->getDefinition());
    }

    // // all changes to other options
    // protected function changeAll(Options $opt)
    // {
    //     $opt->setFromArray([
    //         'default_db_adapter' => $this->getMock('Zend\Db\Adapter\Adapter', array(), array(), '', false),
    //         'db_adapter' => $this->getMock('Zend\Db\Adapter\Adapter', array(), array(), '', false),
    //         'data_cache_config' => [
    //             'adapter' => ['name' => 'apc', 'options' => ['namespaceSeparator' => md5(microtime())]]
    //         ],
    //         'definition_caching' => ! $opt->getDefinitionCaching(),
    //         'definition_cache_config' => [
    //             'adapter' => ['name' => 'apc', 'options' => ['namespaceSeparator' => md5(microtime())]]
    //         ],
    //         'default_entity_class' => md5(microtime()),
    //         'default_hydrator_class' => md5(microtime()),
    //         'caching' => ! $opt->getCaching()
    //     ]);
    //     $opt->addDiscoverDefinitionsPath(md5(microtime()));
    //     return $opt;
    // }
    /**
     * @expectedException BrsZfSloth\Exception\DefinitionConfigNotFoundException
     * @expectedExceptionMessage definition config not found for name "failname"
     */
}