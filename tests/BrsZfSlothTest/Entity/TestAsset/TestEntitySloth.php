<?php

namespace BrsZfSlothTest\Entity\TestAsset;

use BrsZfSloth\Entity\Entity;

class TestEntitySloth extends Entity
{
    protected $outsideDefinition;

    public function setOutsideDefinition($value)
    {
        $this->outsideDefinition = $value;
        return $this;
    }

    public function getOutsideDefinition()
    {
        return $this->outsideDefinition;
    }
}