<?php

namespace BrsZfSlothTest\Definition;

// use StdClass;

use BrsZfSloth\Sloth;
use BrsZfSloth\Definition\Field;
// use BrsZfSloth\Definition\DefinitionProviderInterface;
// use BrsZfSloth\Definition\DefinitionAwareInterface;
// use BrsZfSloth\Definition\Field;
// use BrsZfSloth\Expr;
// use BrsZfSloth\Expr;

/**
 * @group BrsZfSloth
 */
class FieldTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Sloth::reset();
        // Definition::reset();
    }

    /*
     * @expectedException BrsZfSloth\Exception\DefinitionConfigNotFoundException
     * @expectedExceptionMessage definition config not found for name "failname"
     */
    public function testMinConfig()
    {
        $field = new Field('testName');
        $this->assertInstanceOf('BrsZfSloth\Definition\Field', $field);
        $this->assertEquals('testName', $field->getName());
        $this->assertEquals('test_name', $field->getMapping());
    }

    public function testFullConfig()
    {
        $field = new Field('test', [
            // 'type' => 'int'
            // 'type' => 'character varying',
            'type' => 'character varying(16)',
            'default' => 'x',
            'mapping' => 'test_mapping',
            'notNull' => true,
            'primary' => true,
        ]);

        $this->assertEquals('test', $field->getName());
        $this->assertEquals('character varying', $field->getType());
        $this->assertEquals('characterVarying', $field->getAssert());
        $this->assertEquals([16], $field->getAssertParams());
        $this->assertEquals('x', $field->getDefault());
        $this->assertEquals('test_mapping', $field->getMapping());
        $this->assertTrue($field->getNotNull());
        $this->assertTrue($field->getPrimary());
        // mprd($field);
    }

    public static function assertValueProvider()
    {
        return [
            ['text', '', null],
            ['character varying(3)', 'x', 'xxxx'],

            ['integer', 1, 1.1],
            ['smallint', 1, 9999999999],

            ['boolean', true, 1],

            ['date', '2012-12-12', 'xxx'],
            ['datetime', '2012-12-12 12:12:12', 'xxx']
        ];
    }

    /**
     * @dataProvider assertValueProvider
     */
    public function testAssertValue($type, $validValue, $invalidValue)
    {
        $field = new Field('test', $type);
        $field->assertValue($validValue);
    }

    /**
     * @dataProvider assertValueProvider
     * @expectedException BrsZfSloth\Exception\AssertException
     */
    public function testAssertValueFail($type, $validValue, $invalidValue)
    {
        $field = new Field('test', $type);
        $field->assertValue($invalidValue);
    }

    /**
     * @dataProvider assertValueProvider
     * @expectedException BrsZfSloth\Exception\AssertException
     */
    public function testAssertValueWhenConstantValueIsSetFail($type, $validValue, $invalidValue)
    {
        $field = new Field('test', $type);
        $field->setConstantValue('someConstVal');
        $field->assertValue($validValue);
    }

    /**
     * @dataProvider assertValueProvider
     */
    public function testAssertValueWhenConstantValueIsSet($type, $validValue, $invalidValue)
    {
        $field = new Field('test', $type);
        $field->setConstantValue($validValue);
        $field->assertValue($validValue);
    }

    public function testGetDefaultValueWhenConstantValueIsSet()
    {
        $field = new Field('test', 'integer');
        $field->setDefault(1);
        $this->assertEquals(1, $field->getDefault());
        $field->setConstantValue(2);
        $this->assertEquals(2, $field->getDefault());
    }

}