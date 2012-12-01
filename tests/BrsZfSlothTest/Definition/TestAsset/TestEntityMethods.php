<?php

namespace BrsZfSlothTest\Definition\TestAsset;

class TestEntityMethods
{
    protected $id;
    protected $nameX;
    protected $undefinedInDefinition;

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setNameX($nameX)
    {
        $this->nameX = $nameX;
        return $this;
    }

    public function getNameX()
    {
        return $this->nameX;
    }

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