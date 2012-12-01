<?php

namespace BrsZfSloth\Sql\Where;

use BrsZfSloth\Exception;
use BrsZfSloth\Sql\Where;
use BrsZfSloth\Sql\Expr;

abstract class SingleField extends Where
{
    abstract protected function getExpr($field);
    abstract protected function getNegativeExpr($field);

    public function __construct($field, $value = self::UNDEFINED)
    {
        parent::__construct($this->getExpr($field), $this->getNegativeExpr($field));

        if (self::UNDEFINED !== $value) {
            $this->setParam($field, $value);
        }
    }

    public function getField()
    {
        return current($this->getParsedFields());
    }

    protected function getValue()
    {
        return $this->getParam($this->getField());
    }

    public function render(array $params = array())
    {
        switch (count($this->getParsedParams())) {
            case 0: break;
            case 1: $params[$this->getField()] = $this->getValue(); break;
            default: throw new Exception\LogicException(sprintf('Invalid count params in expression "%s". Single field should include only one field', $this));
        }
        return parent::render($params);
    }
}