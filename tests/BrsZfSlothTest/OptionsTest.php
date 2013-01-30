<?php

namespace BrsZfSlothTest;

use BrsZfSloth\Sloth;
use BrsZfSloth\Options;

// use Zend\Db\Adapter\Adapter;

/**
 * @group BrsZfSloth
 */
class OptionsTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Sloth::reset();
    }

    public function testOptions()
    {
        $opt = new Options;


        $this->assertEquals(12, count($opt->toArray())); // new options must be added to changeAll()


        // mprd(count($opt->toArray()));

        // mprd($opt->toArray());
    }

    // all changes to other options
    protected function changeAll(Options $opt)
    {
        $opt->setFromArray([
            'default_db_adapter' => $this->getMock('Zend\Db\Adapter\Adapter', array(), array(), '', false),
            'db_adapter' => $this->getMock('Zend\Db\Adapter\Adapter', array(), array(), '', false),
            'data_cache_config' => [
                'adapter' => ['name' => 'apc', 'options' => ['namespaceSeparator' => md5(microtime())]]
            ],
            'definition_caching' => ! $opt->getDefinitionCaching(),
            'definition_cache_config' => [
                'adapter' => ['name' => 'apc', 'options' => ['namespaceSeparator' => md5(microtime())]]
            ],
            'default_entity_class' => md5(microtime()),
            'default_hydrator_class' => md5(microtime()),
            'caching' => ! $opt->getCaching()
        ]);
        $opt->addDiscoverDefinitionsPath(md5(microtime()));
        return $opt;
    }
    /**
     * @expectedException BrsZfSloth\Exception\DefinitionConfigNotFoundException
     * @expectedExceptionMessage definition config not found for name "failname"
     */
}