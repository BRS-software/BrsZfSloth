<?php
namespace BrsZfSlothTest\Definition;

use StdClass;

use BrsZfSloth\Sloth;
use BrsZfSloth\Definition\Definition;
use BrsZfSloth\Definition\Field;
use BrsZfSloth\Definition\DefinitionProviderInterface;
use BrsZfSloth\Definition\DefinitionAwareInterface;
// use BrsZfSloth\Definition\Field;
// use BrsZfSloth\Expr;
// use BrsZfSloth\Expr;

/**
 * @group BrsZfSloth
 */
class DefinitionTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Sloth::reset();
        Definition::reset();
    }

    /**
     * @expectedException BrsZfSloth\Exception\DefinitionConfigNotFoundException
     * @expectedExceptionMessage definition config not found for name "failname"
     */
    public function testDiscoverDefinitionFail()
    {
        Definition::discoverConfig('failname');
    }

    /**
     * @expectedException BrsZfSloth\Exception\UnsupportedException
     */
    public function testDiscoverDefinitionUnsupported()
    {
        Sloth::getOptions()->addDiscoverDefinitionsPath(__DIR__ . '/TestAsset');
        Definition::discoverConfig('testDefinitionUnsupported');
    }

    /**
     * @expectedException BrsZfSloth\Exception\InvalidArgumentException
     * @expectedExceptionMessage argument must be definition name string or config array or DefinitionAwareInterface or DefinitionProviderInterface
     */
    public function testGetCachedInstanceFail()
    {
        Definition::getCachedInstance(new StdClass);
    }

    public function testCreateCachedInstanceFromName()
    {
        Sloth::getOptions()->addDiscoverDefinitionsPath(__DIR__ . '/TestAsset');

        // mprd(Sloth::getOptions()->toarray());
        $def = Definition::getCachedInstance('testDefinition');
        $this->assertInstanceOf('BrsZfSloth\Definition\Definition', $def);
    }

    // public function testCreateCachedInstanceFromDefinitionAware()
    // {
    //     Sloth::getOptions()->addDiscoverDefinitionsPath(__DIR__ . '/TestAsset');

    //     $definitionAware = $this->getMock('BrsZfSloth\Definition\DefinitionAwareInterface');
    //     $definitionAware
    //         ->expects($this->any())
    //         ->method('getDefinitionName')
    //         ->will($this->returnValue('testDefinition'))
    //         ;

    //     // mprd(Sloth::getOptions()->toarray());
    //     $def = Definition::getCachedInstance($definitionAware);
    //     $this->assertInstanceOf('BrsZfSloth\Definition\Definition', $def);
    // }

    public function testCreateCachedInstanceFromProvider()
    {
        $definitionProvider = $this->getMock('BrsZfSloth\Definition\DefinitionProviderInterface');
        $definitionProvider::staticExpects($this->any())
            ->method('getDefinitionConfig')
            ->will($this->returnValue([
                'name' => 'testdef',
                'table' => 'testtable',
            ]))
        ;
        // mprd($definitionProvider->getDefinitionConfig());

        $def = Definition::getCachedInstance($definitionProvider);
        $this->assertInstanceOf('BrsZfSloth\Definition\Definition', $def);
    }

    public function testCreateCachedInstanceFromArray()
    {
        $def = Definition::getCachedInstance([
            'name' => 'test',
            'table' => 'test',
        ]);
        $this->assertInstanceOf('BrsZfSloth\Definition\Definition', $def);
    }

    /**
     * Test could not work because could not read cofig from existing testDefinition.json file
     * @expectedException BrsZfSloth\Exception\IncorrectDefinitionException
     * @expectedExceptionMessage parametr "table" is required in definition config
     */
    public function testCreateCachedInstanceFromArrayFail()
    {
        Sloth::getOptions()->addDiscoverDefinitionsPath(__DIR__ . '/TestAsset');

        $def = Definition::getCachedInstance([
            'name' => 'testDefinition',
            // 'table' => 'test',
        ]);
        $this->assertInstanceOf('BrsZfSloth\Definition\Definition', $def);
    }

    // public function testGettingDefaultOptons()
    // {
    //     $def = new Definition([
    //         ''
    //     ])
    // }

    public function testCreateFromProvider()
    {
        $definitionProvider = $this->getMock('BrsZfSloth\Definition\DefinitionProviderInterface');
        $definitionProvider::staticExpects($this->any())
            ->method('getDefinitionConfig')
            ->will($this->returnValue([
                'name' => 'testname',
                'table' => 'testtable',
            ]))
        ;

        $def = new Definition($definitionProvider);
        $this->assertEquals('testname', $def->getName());
        $this->assertEquals('testtable', $def->getTable());
    }

    public function testFullConfig()
    {
        $entityClass = $this->getMock('StdClass');
        $collectionClass = $this->getMock('ArrayAccess');

        $def = new Definition([
            'name' => 'testname',
            'schema' => 'testschema',
            'table' => 'testtable',
            'entityClass' => get_class($entityClass),
            'collectionClass' => get_class($collectionClass),
            'hydratorClass' => 'Zend\Stdlib\Hydrator\ClassMethods',
            'defaultOrder' => ['name' => SORT_DESC],
            'fields' => [
                'id' => 'integer',
                'firstName' => 'text',
            ]
        ]);
        $this->assertEquals('testname', $def->getName());
        $this->assertEquals('testschema', $def->getSchema());
        $this->assertEquals('testtable', $def->getTable());
        $this->assertEquals(get_class($entityClass), $def->getOptions()->getEntityClass());
        $this->assertEquals(get_class($collectionClass), $def->getOptions()->getCollectionClass());
        $this->assertInstanceOf('Zend\Stdlib\Hydrator\ClassMethods', $def->getOptions()->getHydrator());
        $this->assertTrue($def->hasField('id'));
        $this->assertFalse($def->hasField('idx'));
        $this->assertInstanceOf('BrsZfSloth\Definition\Field', $def->getField('id'));
        $this->assertEquals(['id' => 'id', 'firstName' => 'first_name'], $def->getMapping());
        $this->assertEquals(2, count($def));
        $this->assertEquals(2, count($def->getFields()));
        $this->assertInstanceOf('BrsZfSloth\Definition\Field', $def->getFields()['id']);
        $this->assertInstanceOf('BrsZfSloth\Definition\Field', $def->getFields()['firstName']);
        $this->assertTrue(isset($def['id']));
        $this->assertFalse(isset($def['idx']));
        $this->assertEquals('name', $def->getDefaultOrder()->getField());
        $this->assertEquals(SORT_DESC, $def->getDefaultOrder()->getDirect());
    }

    public function testMinConfig()
    {
        $def = new Definition([
            'name' => 'testname',
            'table' => 'testtable',
            'fields' => [
                'id' => 'integer',
            ]
        ]);

        $this->assertEquals('testname', $def->getName());
        $this->assertEquals('testtable', $def->getTable());
        $this->assertTrue($def->hasField('id'));
        $this->assertFalse($def->hasField('idx'));
        $this->assertInstanceOf('BrsZfSloth\Definition\Field', $def->getField('id'));
        $this->assertEquals(['id' => 'id'], $def->getMapping());
        $this->assertEquals(1, count($def));
        $this->assertEquals(1, count($def->getFields()));
        $this->assertInstanceOf('BrsZfSloth\Definition\Field', $def->getFields()['id']);
        $this->assertEquals('id', $def->getDefaultOrder()->getField());
        $this->assertEquals(SORT_ASC, $def->getDefaultOrder()->getDirect());
    }


    public function testOptionsConfig()
    {
        // mprd(new TestAsset\TestEntityMethods);
        // $entityClass = $this->getMock('StdClass');
        // class_alias(get_class($entityClass), 'Test\Entity2');

        // $hydrator = $this->getMock('Zend\Stdlib\Hydrator\HydratorInterface');
        // class_alias(get_class($hydrator), 'Test\Hydrator');

        Sloth::configure([
            'default_entity_class' => 'BrsZfSlothTest\Definition\TestAsset\TestEntityMethods',
            // 'default_repository_class' => 'Default\Repository\Class',
            'default_hydrator_class' => 'Zend\Stdlib\Hydrator\ClassMethods',
        ]);
        $def = new Definition([
            'name' => 'testname',
            'table' => 'testtable',
            'fields' => [
                'id' => 'integer',
                'firstName' => 'text',
            ]
        ]);
        // mprd(1);
        $this->assertEquals('testname', $def->getName());
        $this->assertEquals('testtable', $def->getTable());
        $this->assertTrue($def->hasField('id'));
        $this->assertFalse($def->hasField('idx'));
        $this->assertInstanceOf('BrsZfSloth\Definition\Field', $def->getField('id'));
        $this->assertEquals(['id' => 'id', 'firstName' => 'first_name'], $def->getMapping());
        $this->assertEquals(2, count($def));
        $this->assertEquals(2, count($def->getFields()));
        $this->assertInstanceOf('BrsZfSloth\Definition\Field', $def->getFields()['id']);
        $this->assertInstanceOf('BrsZfSloth\Definition\Field', $def->getFields()['firstName']);
        // mprd($def->toarray());
    }

    /**
     * @dataProvider faliConfigProvider
     * @expectedException BrsZfSloth\Exception\IncorrectDefinitionException
     */
    public function testConfigFail($config)
    {
        new Definition($config);
    }


    public static function faliConfigProvider()
    {
        return [
            [[]],

            [[
                'name' => 'test',
            ]],
        ];
    }

    public function testAssertEntityClassOk()
    {
        $def = new Definition([
            'name' => 'test',
            'table' => 'test',
            'entityClass' => 'StdClass'
        ]);
        $def->assertEntityClass(new StdClass);
    }

    /**
     * @expectedException BrsZfSloth\Exception\InvalidArgumentException
     * @expectedExceptionMessage entity must be instance of BrsZfSloth\Entity\Entity, given stdClass
     */
    public function testAssertEntityClassFail()
    {
        $def = new Definition([
            'name' => 'test',
            'table' => 'test',
            'entityClass' => 'BrsZfSloth\Entity\Entity'
        ]);
        $def->assertEntityClass(new StdClass);
    }

    public function testAssertCollectionClassOk()
    {
        $def = new Definition([
            'name' => 'test',
            'table' => 'test',
            'collectionClass' => get_class($collection = $this->getMock('BrsZfSloth\Collection\Collection'))
        ]);
        $def->assertCollectionClass($collection);
    }

    /**
     * @expectedException BrsZfSloth\Exception\InvalidArgumentException
     */
    public function testAssertCollectionClassFail()
    {
        $def = new Definition([
            'name' => 'test',
            'table' => 'test',
            'collectionClass' => get_class($collection = $this->getMock('BrsZfSloth\Collection\Collection'))
        ]);
        $def->assertCollectionClass($this->getMock('ArrayAccess'));
    }

    public function testRemap()
    {
        $def = new Definition([
            'name' => 'test',
            'table' => 'test',
            'fields' => [
                'id' => [
                    'type' => 'integer',
                    'mapping' => 'id_x'
                ],
                'nameX' => 'text'
            ]
        ]);

        $this->assertEquals([
            'id' => 1,
            'nameX' => 'test'
        ], $def->remapToEntity([
            'id_x' => 1,
            'name_x' => 'test'
        ]));

        $this->assertEquals([
            'id_x' => 1,
            'name_x' => 'test'
        ], $def->remapToRepository([
            'id' => 1,
            'nameX' => 'test'
        ]));
    }

    /**
     * @expectedException BrsZfSloth\Exception\UnmappedException
     * @expectedExceptionMessage not found mapping for xxx in definition test
     */
    public function testRemapFail()
    {
        $def = new Definition([
            'name' => 'test',
            'table' => 'test',
        ]);
        $def->remapToEntity(['xxx' => 1], true);
    }

    public function testConfigureHydrator()
    {
        $def = new Definition([
            'name' => 'testname',
            'table' => 'testtable',
            // 'hydratorClass' => 'Zend\Stdlib\Hydrator\ClassMethods',
            'fields' => [
                'bool' => 'boolean',
                'null' => 'character varying',
            ]
        ]);

        $this->assertInstanceOf(Field::$types['boolean']['hydratorStrategyClass'], $def->getHydrator()->getStrategy('bool'));
    }
    // public static function toArrayProvider()
    // {
    //     $def = new Definition([
    //         'name' => 'test',
    //         'table' => 'test',
    //         'fields' => [
    //             'id' => [
    //                 'assert' => 'integer',
    //                 'mapping' => 'id_x'
    //             ],
    //             'nameX' => 'text'
    //         ]
    //     ]);

    //     return [
    //         // Sloth entity with sloth hydrator
    //         [
    //             $def,
    //             'BrsZfSloth\Hydrator\Hydrator',
    //             (new TestAsset\TestEntitySloth)
    //                 ->setDefinition($def)
    //                 ->setId(1)
    //                 ->setNameX('x')
    //                 ->setUndefinedInDefinition('y')
    //         ],
    //         // Sloth entity with staandard methods hydrator - it does not work because set/getRepository is causes fail
    //         // [
    //         //     $def,
    //         //     'Zend\Stdlib\Hydrator\ClassMethods',
    //         //     (new TestAsset\TestEntitySloth)
    //         //         ->setDefinition($def)
    //         //         ->setId(1)
    //         //         ->setNameX('x')
    //         //         ->setUndefinedInDefinition('y')
    //         // ],
    //         // some class with methods setters and getters (not inherits by Sloth entityt)
    //         [
    //             $def,
    //             'Zend\Stdlib\Hydrator\ClassMethods',
    //             (new TestAsset\TestEntityMethods)
    //                 ->setId(1)
    //                 ->setNameX('x')
    //                 ->setUndefinedInDefinition('y')
    //         ],
    //         // StdClass entity with properties
    //         [
    //             $def,
    //             'Zend\Stdlib\Hydrator\ObjectProperty',
    //             (object) [
    //                 'id' => 1,
    //                 'nameX' => 'x',
    //                 'undefinedInDefinition' => 'y',
    //             ]
    //         ],
    //     ];
    // }

    // /**
    //  * @dataProvider toArrayProvider
    //  */
    // public function testToArray($def, $hydratorClass, $entity)
    // {
    //     $def->getOptions()->setEntityClass(get_class($entity));
    //     $def->setHydrator(new $hydratorClass);
    //     // mprd(99);

    //     $this->assertSame([
    //         'id_x' => 1,
    //         'name_x' => 'x'
    //     ], $def->entityToRepository($entity));

    //     $this->assertSame([
    //         'id' => 1,
    //         'nameX' => 'x'
    //     ], $def->entityToArray($entity));
    // }

    // public function testRequired()
    // {
    //     $def = new Definition([
    //         'name' => 'test',
    //         'table' => 'test',
    //         'fields' => [
    //             'id' => [
    //                 'assert' => 'integer',
    //                 'required' => true
    //             ],
    //             'nameX' => 'text'
    //         ]
    //     ]);

    //     $entity = (object) [];
    //     $def->assertRequiredFields($entity);
    //     $entity = (object) ['id' => 1];
    // }

    // public function testToArrayEntityMethod()
    // {
    //     $def = new Definition([
    //         'name' => 'test',
    //         'table' => 'test',
    //         'hydratorClass' => 'Zend\Stdlib\Hydrator\ClassMethods',
    //         'entityClass' => 'BrsZfSlothTest\Definition\TestAsset\TestEntityMethods',
    //         'fields' => [
    //             'id' => [
    //                 'assert' => 'integer',
    //                 'mapping' => 'id_x'
    //             ],
    //             'nameX' => 'text'
    //         ]
    //     ]);

    //     $entity = (new TestAsset\TestEntityMethods)
    //         ->setId(1)
    //         ->setNameX('x')
    //         ->setUndefinedInDefinition('y')
    //     ;
    //     $this->assertSame([
    //         'id_x' => 1,
    //         'name_x' => 'x'
    //     ], $def->entityToRepository($entity));

    //     $this->assertSame([
    //         'id' => 1,
    //         'nameX' => 'x'
    //     ], $def->entityToArray($entity));
    // }


    // public function testToArrayEntityProperty()
    // {
    //     $def = new Definition([
    //         'name' => 'test',
    //         'table' => 'test',
    //         'hydratorClass' => 'Zend\Stdlib\Hydrator\ObjectProperty',
    //         'fields' => [
    //             'id' => [
    //                 'assert' => 'integer',
    //                 'mapping' => 'id_x'
    //             ],
    //             'nameX' => 'text'
    //         ]
    //     ]);

    //     $entity = (object) [
    //         'id' => 1,
    //         'nameX' => 'x',
    //         'undefinedInDefinition' => 'y',
    //     ];
    //     $this->assertSame([
    //         'id_x' => 1,
    //         'name_x' => 'x'
    //     ], $def->entityToRepository($entity));

    //     $this->assertSame([
    //         'id' => 1,
    //         'nameX' => 'x'
    //     ], $def->entityToArray($entity));
    // }

    // public function testToArrayEntitySloth()
    // {
    //     $def = new Definition([
    //         'name' => 'test',
    //         'table' => 'test',
    //         'hydratorClass' => 'BrsZfSloth\Hydrator\Hydrator',
    //         'entityClass' => 'BrsZfSlothTest\Definition\TestAsset\TestEntitySloth',
    //         'fields' => [
    //             'id' => [
    //                 'assert' => 'integer',
    //                 'mapping' => 'id_x'
    //             ],
    //             'nameX' => 'text'
    //         ]
    //     ]);

    //     $entity = (new TestAsset\TestEntitySloth)
    //         ->setDefinition($def)
    //         ->setId(1)
    //         ->setNameX('x')
    //         ->setUndefinedInDefinition('y')
    //     ;
    //     $this->assertSame([
    //         'id_x' => 1,
    //         'name_x' => 'x'
    //     ], $def->entityToRepository($entity));

    //     $this->assertSame([
    //         'id' => 1,
    //         'nameX' => 'x'
    //     ], $def->entityToArray($entity));
    // }
    // public function testGetEntityValues()
    // {
    //     $def = new Definition([
    //         'name' => 'test',
    //         'table' => 'test',
    //         'fields' => [
    //             'id' => 'integer',
    //             'firstName' => 'text'
    //         ]
    //     ]);

    //     $entity = (object) [
    //         'id' => 1,
    //         'firstName' => 'test',
    //         'otherProp' => 'xxx',
    //     ];

    //     mprd($def->getEntityValues($entity));
    // }





    // defaultHydrator
    // getprimary
    // getFieldBySetter fail
    // getFieldByGetter fail


    // public function testGetEntityClass()
    // {
    //     $def = new Definition([
    //         'name' => 'testname',
    //         'entityClass' => 'Some\Entity\Class'
    //     ]);
    //     $this->assertEquals('Some\Entity\Class', $def->getEntityClass());
    // }

    // public function testGetTable()
    // {
    //     $def = new Definition([
    //         'name' => 'testname',
    //         'entityClass' => 'Some\Entity\Class'
    //     ]);
    //     $this->assertEquals('Some\Entity\Class', $def->getEntityClass());
    // }



    // public function tearDown()
    // {
    //     // your code here
    // }
}