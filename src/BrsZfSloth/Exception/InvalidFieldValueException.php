<?php
namespace BrsZfSloth\Exception;

use BrsZfSloth\Definition\Definition;
use BrsZfSloth\Definition\Field;

class InvalidFieldValueException extends \InvalidArgumentException implements ExceptionInterface
{
    protected $definition;
    protected $field;

    public function __construct(Definition $definition, Field $field, \InvalidArgumentException $prev = null)
    {
        $this->definition = $definition;
        $this->field = $field;

        parent::__construct(
            ExceptionTools::msg('field %s::%s is invalid - %s', $definition->getName(), $field->getName(), $prev->getMessage()),
            0,
            $prev
        );
    }

    public function getDefinition()
    {
        return $this->definition;
    }

    public function getField()
    {
        return $this->field;
    }
}