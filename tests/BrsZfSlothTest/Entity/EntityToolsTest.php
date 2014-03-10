<?php

namespace BrsZfSlothTest\Entity;

use StdClass;

use BrsZfSloth\Sloth;
use BrsZfSloth\Definition\Definition;
use BrsZfSloth\Entity\EntityTools;

use BrsZfSlothTest\Entity\TestAsset\TestEntityMethods;
use BrsZfSlothTest\Entity\TestAsset\TestEntitySloth;

/**
 * @group BrsZfSloth
 */
class EntityToolsTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Sloth::reset();
        Definition::reset();
    }

    /*
     * @expectedException BrsZfSloth\Exception\DefinitionConfigNotFoundException
     * @expectedExceptionMessage definition config not found for name "givenDefinitionName" (paths: )
     */
    // public function testDefinitionPrior()
    // {
    //     EntityTools::getDefinition(
    //         $this->getMock('BrsZfSloth\Definition\DefinitionAwareInterface'),
    //         'givenDefinitionName'
    //     );
    // }

    public function testDiff()
    {
        $this->assertEquals([], EntityTools::diff([], []));
        $this->assertEquals([], EntityTools::diff(['a' => 1], ['a' => 1]));
        $this->assertEquals(['a' => ['new' => 1]], EntityTools::diff(['a' => 1], []));
        $this->assertEquals(['a' => ['new' => 1, 'old' => 2]], EntityTools::diff(['a' => 1], ['a' => 2]));
    }

    public static function toEntityProvider()
    {
        $def = new Definition([
            'name' => 'test',
            'table' => 'test',
            'fields' => [
                // specified mapping
                'id' => [
                    'type' => 'integer',
                    'mapping' => 'id_x',
                ],
                // default hydrator strategy: boolean
                'isActive' => 'boolean',
                // a set value
                'firstName' => 'text' ,
                // a not set value
                'comment' => 'text',
            ]
        ]);

        return [
            // Sloth entity with sloth hydrator
            [
                $def,
                new \BrsZfSloth\Hydrator\Hydrator,
                (new TestAsset\TestEntitySloth)
                    ->setDefinition($def)
                    ->setId(1)
                    ->setIsActive(false)
                    ->setFirstName('x')
                    ->setOutsideDefinition('y')
            ],
            // Sloth entity with staandard methods hydrator - it does not work because set/getRepository is causes fail
            // [
            //     $def,
            //     'Zend\Stdlib\Hydrator\ClassMethods',
            //     (new TestAsset\TestEntitySloth)
            //         ->setDefinition($def)
            //         ->setId(1)
            //         ->setNameX('x')
            //         ->setUndefinedInDefinition('y')
            // ],
            // some class with methods setters and getters (not inherits by Sloth entityt)
            [
                $def,
                new \Zend\Stdlib\Hydrator\ClassMethods(false),
                (new TestAsset\TestEntityMethods)
                    ->setId(1)
                    ->setIsActive(false)
                    ->setFirstName('x')
                    ->setOutsideDefinition('y')
            ],
            // StdClass entity with properties
            [
                $def,
                new \Zend\Stdlib\Hydrator\ObjectProperty,
                (object) [
                    'id' => 1,
                    'isActive' => false,
                    'firstName' => 'x',
                    'undefinedInDefinition' => 'y',
                ]
            ],
        ];
    }

    /**
     * @dataProvider toEntityProvider
     */
    public function testToRepository($def, $hydrator, $entity)
    {
        $def->getOptions()->setEntityClass(get_class($entity));
        $def->setHydrator($hydrator);

        $this->assertSame([
            'id_x' => 1,
            'is_active' => 0,
            'first_name' => 'x',
            'comment' => null,
        ], EntityTools::ToRepository($entity, $def));
    }

    /**
     * @dataProvider toEntityProvider
     */
    public function testToArray($def, $hydrator, $entity)
    {
        $def->getOptions()->setEntityClass(get_class($entity));
        $def->setHydrator($hydrator);

        $this->assertSame([
            'id' => 1,
            'isActive' => false,
            'firstName' => 'x',
            'comment' => null,
        ], EntityTools::toArray($entity, $def));
    }

    /**
     * @dataProvider toEntityProvider
     */
    public function testSetValue($def, $hydratorClass, $entity)
    {
        $def->getOptions()->setEntityClass(get_class($entity));
        $def->setHydrator(new $hydratorClass);

        EntityTools::setValue('firstName', 'y', $entity, $def);
        $this->assertEquals('y', EntityTools::getValue('firstName', $entity, $def));

    }

    /**
     * @dataProvider toEntityProvider
     */
    public function testGetValue($def, $hydratorClass, $entity)
    {
        $def->getOptions()->setEntityClass(get_class($entity));
        $def->setHydrator(new $hydratorClass);

        $this->assertEquals('1', EntityTools::getValue('id', $entity, $def));
        $this->assertEquals('x', EntityTools::getValue('firstName', $entity, $def));

    }

    /**
     * @dataProvider toEntityProvider
     * @expectedException BrsZfSloth\Exception\FieldRequiredException
     * expectedExceptionMessage field test::id is required for entity class stdClass
     */
    public function testRequired($def, $hydratorClass, $entity)
    {
        $def->getOptions()->setEntityClass(get_class($entity));
        $def->setHydrator(new $hydratorClass);

        $def->getField('firstName')->setNotNull(true);
        EntityTools::setValue('firstName', null, $entity, $def);
        EntityTools::assertRequiredFields($entity, $def);
    }

    /**
     * @dataProvider toEntityProvider
     */
    public function testGetDefaultValue($def, $hydratorClass, $entity)
    {
        $def->getOptions()->setEntityClass(get_class($entity));
        $def->setHydrator(new $hydratorClass);

        $def->getField('comment')->setDefault('defaultValue');
        $this->assertEquals('defaultValue', EntityTools::getValue('comment', $entity, $def));

        EntityTools::setValue('comment', 'xxx', $entity, $def);
        $this->assertEquals('xxx', EntityTools::getValue('comment', $entity, $def));
    }

    /**
     * @dataProvider toEntityProvider
     * @expectedException BrsZfSloth\Exception\AssertException
     */
    public function testSetConstantValue($def, $hydratorClass, $entity)
    {
        $def->getOptions()->setEntityClass(get_class($entity));
        $def->setHydrator(new $hydratorClass);

        $def->getField('firstName')->setConstantValue('constValue');
        EntityTools::setValue('firstName', 'xxx', $entity, $def);
        EntityTools::assertFieldValue('firstName', $entity, $def);
    }

    /**
     * @dataProvider toEntityProvider
     */
    public function testAssertFieldValue($def, $hydratorClass, $entity)
    {
        $def->getOptions()->setEntityClass(get_class($entity));
        $def->setHydrator(new $hydratorClass);

        EntityTools::assertFieldValue('firstName', $entity, $def);
    }

    /**
     * @dataProvider toEntityProvider
     * @expectedException BrsZfSloth\Exception\AssertException
     */
    public function testAssertFieldValueFail($def, $hydratorClass, $entity)
    {
        $def->getOptions()->setEntityClass(get_class($entity));
        $def->setHydrator(new $hydratorClass);
        $def->getField('firstName')->setType('integer');

        EntityTools::assertFieldValue('firstName', $entity, $def);
    }

    public function testSanitize()
    {
        $def = new Definition([
            'name' => 'test',
            'table' => 'test',
            'fields' => [
                'firstName' => 'text' ,
            ]
        ]);
        $sanitized = EntityTools::sanitize([
            'firstName' => 'xxx',
            'ignoreThis' => 'yyy',
        ], $def);

        $this->assertEquals($sanitized, ['firstName' => 'xxx']);
    }

    // /**
    //  * @dataProvider toEntityProvider
    //  */
    // public function testAssertAllFieldsValues($def, $hydratorClass, $entity)
    // {
    //     $def->getOptions()->setEntityClass(get_class($entity));
    //     $def->setHydrator(new $hydratorClass);

    //     EntityTools::assertAllFieldsValues($entity, $def);
    // }

    // /**
    //  * @dataProvider toEntityProvider
    //  */
    // public function testAssertAllFieldsValuesFail($def, $hydratorClass, $entity)
    // {
    //     $def->getOptions()->setEntityClass(get_class($entity));
    //     $def->setHydrator(new $hydratorClass);
    //     $def->getField('firstName')->setType('integer');

    //     EntityTools::assertAllFieldsValues($entity, $def);
    // }

    /**
     * @dataProvider toEntityProvider
     */
    public function testValidate($def, $hydratorClass, $entity)
    {
        $def->getOptions()->setEntityClass(get_class($entity));
        $def->setHydrator(new $hydratorClass);

        EntityTools::validate($entity, $def);
    }

    /**
     * @dataProvider toEntityProvider
     * @expectedException BrsZfSloth\Exception\InvalidFieldValueException
     */
    public function testValidateFail($def, $hydratorClass, $entity)
    {
        $def->getOptions()->setEntityClass(get_class($entity));
        $def->setHydrator(new $hydratorClass);
        $def->getField('id')->setNotNull(true);

        EntityTools::setValue('id', null, $entity, $def);
        EntityTools::validate($entity, $def);
        mprd(9);
    }

}