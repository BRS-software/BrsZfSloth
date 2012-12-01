<?php

namespace BrsZfSlothTest\Definition\TestAsset;

use BrsZfSloth\Entity\Entity;

class TestEntitySloth extends Entity
{
    public function setUndefinedInDefinition($value)
    {
        $this->undefinedInDefinition = $value;
        return $this;
    }

    public function getUndefinedInDefinition()
    {
        return $this->undefinedInDefinition;
    }
}