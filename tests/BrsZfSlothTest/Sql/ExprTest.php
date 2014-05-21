<?php

namespace BrsZfSlothTest\Sql;

use BrsZfSloth\Sql\Expr;
use BrsZfSloth\Definition\Definition;

/**
 * @group BrsZfSloth
 */
class ExprTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->definition = $this->getMockDefinition();
        $this->getMockField('testField', ['mapping' => 'test_field'], $this->definition);
        // mprd($this->definition->getField('testField')->getMapping());
    }

    protected function getMockDefinition()
    {
        $definition = $this->getMock(
            'BrsZfSloth\Definition\Definition',
            array(),
            array(),
            '',
            false
            // array(['name' => 'test'])
        );
        $definition // DefinitionAwareInterface implementation
            ->expects($this->any())
            ->method('getDefinition')
            ->will($this->returnValue($definition))
        ;
        return $definition;
    }
    protected function getMockField($name, array $params = array(), Definition $definition = null)
    {
        $field = $this->getMock(
            'BrsZfSloth\Definition\Field',
            array(),
            array($name, array('assert'))
        );
        $field
            ->expects($this->any())
            ->method('getMapping')
            ->will($this->returnValue(isset($params['mapping']) ? $params['mapping'] : $name))
        ;
        if ($definition) {
            $definition
                ->expects($this->any())
                ->method('getField')
                ->with($name)
                ->will($this->returnValue($field))
            ;
        }
        return $field;
    }


    public function testExpr()
    {
        $e = new Expr($estr = 'some expR');
        $this->assertEquals($estr, (string) $e);
    }

    public function testGetParsedFields()
    {
        $e = new Expr('{field1}, {field2}, :field3');
        $this->assertEquals(array('field1','field2'), $e->getParsedFields());
    }

    public function testQuestionMarkAsParams()
    {
        $e = new Expr(':? :::?');
        $this->assertEquals(array('0','1'), $e->getParsedParams());
    }

    public function testAssignValuesToQuestionMarkAsParams()
    {
        $e = new Expr(':? :::?');
        $this->assertEquals('x ::y', (string) $e->render(['x','y']));
    }

    /**
     * @expectedException BrsZfSloth\Exception\NotSetException
     */
    public function testMissingAlias()
    {
        (new Expr('{aliasname.testField}'))->render();
    }

    public function testDefaultDefinition()
    {
        $e = new Expr('{testField}');
        $e->setDefaultDefinition($this->definition);
        $this->assertEquals(
            'test_field',
            (string) $e->render()
        );
    }

    public function testTwoAssignedDefinitions()
    {
        $e = new Expr('{alias1.testField}={alias2.testField2}');

        $d2 = $this->getMockDefinition();
        $this->getMockField('testField2', array('mapping' => 'test_field2'), $d2);

        $e->addDefinitions([
            'alias1' => $this->definition,
            'alias2' => $d2
        ]);

        $this->assertEquals(
            'test_field=test_field2',
            (string) $e->render()
        );
    }

    public function testDefinitionWithTable()
    {
        $this->definition
            ->expects($this->any())
            ->method('getTable')
            ->will($this->returnValue('test_table'))
        ;

        $e = new Expr('{testField} = \'test\'');
        $e->setDefaultDefinition($this->definition);

        $this->assertEquals(
            'test_table.test_field = \'test\'',
            (string) $e->render()
        );
    }

    public function testGetDefAttr()
    {
        $e = new Expr('{:table},{alias1:table},{alias1:defaultOrder}');

        $d = $this->getMockDefinition();
        $d
            ->expects($this->any())
            ->method('getTable')
            ->will($this->returnValue('test_table'))
        ;
        $d
            ->expects($this->any())
            ->method('getDefaultOrder')
            ->will($this->returnValue('id asc'))
        ;
        // dbgd($d->getTable());

        $e
            ->addDefinitions(['alias1' => $d])
            ->setDefaultDefinition($d)
        ;

        $this->assertEquals(
            'test_table,test_table,id asc',
            (string) $e->render()
        );
    }

    public function testWithParamsAndDefinitions()
    {
        $e = new Expr('fn({testField}) = fn(:field1,:param2::text)', [
            'field1' => 'assigned1',
            'param2' => 'assigned2'
        ]);
        $e->setDefaultDefinition($this->definition);

        $this->assertEquals(
            'fn(test_field) = fn(assigned1,assigned2::text)',
            (string)$e->render()
        );
        $this->assertEquals(
            'fn(test_field) = fn(reassigned1,assigned2::text)',
            (string)$e->setParams(['field1' => 'reassigned1'])->render()
        );
    }

    // public function testWithParamsAndDefinitions2()
    // {
    //     $e = new Expr("{testField} @> ':path' AND {testField}=:testField", ['path' => 'x', 'testField' => 'y']);
    //     $e->setDefaultDefinition($this->definition);
    //     $this->assertEquals(
    //         "test_field @> 'x' AND test_field=y",
    //         (string) $e->render()
    //     );
    // }

    /**
     * @expectedException BrsZfSloth\Exception\NotExistsException
     * @expectedExceptionMessage Param "some" not exists in expression "{some}"
     */
    public function testSetUndefinedParam()
    {
        $e = new Expr('{some}');
        $e->setParam('some');
    }

    public function testUsingExprAsParamVal()
    {
        $e = new Expr(':some::text');
        $this->assertEquals("assignedValue::text", (string) $e->setParam('some', new Expr('assignedValue'))->render());
    }

    public function testBeginningFromVar()
    {
        $e = new Expr(':some::text');
        $this->assertEquals("assignedValue::text", (string) $e->setParam('some', 'assignedValue')->render());
    }

    // public function testDoubleUseTheSameParam()
    // {
    //     $e = new Expr('`:some`1 `:some`2');
    //     $this->assertEquals("assignedValue::text", (string) $e->setParam('some', 'xxx')->render());
    // }

    /**
     * @expectedException BrsZfSloth\Exception\NotExistsException
     * @expectedExceptionMessage Param "text" not exists in expression "::text"
     */
    public function testBeginningFromDoubleColon()
    {
        $e = new Expr('::text');
        $e->setParam('text','assignedValue')->render();
    }

    /**
     * @expectedException BrsZfSloth\Exception\RuntimeException
     * @expectedExceptionMessage Undefined param "val1" in expression "fn(:val1, :val2)"
     */
    public function testUnassignedValue()
    {
        $e = new Expr('fn(:val1, :val2)');
        // mprd($e->parse());
        $e->render();
    }

    /**
     * @expectedException BrsZfSloth\Exception\RuntimeException
     * @expectedExceptionMessage Undefined param "val1" in expression "fn(:val1, :val2)"
     */
    public function testUnassignedValue2() {
        $e = new Expr('fn(:val1, :val2)');
        $e->render(array('val2'=>'stop_connect_error(oid)'));
    }

    // public function tearDown()
    // {
    //     // your code here
    // }
}