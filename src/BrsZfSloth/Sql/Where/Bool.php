<?php

namespace BrsZfSloth\Sql\Where;

use BrsZfSloth\Exception;

class Bool extends SingleField
{
    public function setParam($name, $value = self::UNDEFINED)
    {
        $this->parseCache = null;

        if (! is_bool($value)) {
            throw new Exception\InvalidArgumentException('value must be boolean type');
        }

        if ($value) {
            $this->positiveExpr = $this->expr = sprintf('{%s}', $name);
            $this->negativeExpr = sprintf('NOT {%s}', $name);
        } else {
            $this->positiveExpr = $this->expr = sprintf('NOT {%s}', $name);
            $this->negativeExpr = sprintf('{%s}', $name);
        }
        // parent::setParam($name, $value);
    }

    protected function getExpr($field)
    {
        // return sprintf('{%s}=:%s', $field, $field);
        return '1=0';
    }

    protected function getNegativeExpr($field)
    {
        // return 'NOT '.$this->getExpr($field);
        return '1=0';
    }
}