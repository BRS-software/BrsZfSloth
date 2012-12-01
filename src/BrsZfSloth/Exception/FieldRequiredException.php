<?php

namespace BrsZfSloth\Exception;

use BrsZfSloth\Entity\Entity;
use BrsZfSloth\Definition\Definition;
use BrsZfSloth\Definition\Field;

class FieldRequiredException extends \InvalidArgumentException implements ExceptionInterface
{
    protected $definition;
    protected $field;
    protected $entity;

    public function __construct(Definition $definition, Field $field, $entity)
    {
        $this->definition = $definition;
        $this->field = $field;
        $this->entity = $entity;

        parent::__construct(
            sprintf('field %s::%s is required for entity class %s', $definition->getName(), $field->getName(), get_class($entity))
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

    public function getModel()
    {
        return $this->_entity;
    }
}