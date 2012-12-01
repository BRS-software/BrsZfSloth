<?php

namespace BrsZfSlothTest\Entity\TestAsset;

class TestEntityMethods
{
    protected $id;
    protected $isActive;
    protected $firstName;
    protected $comment;
    protected $outsideDefinition;

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setIsActive($flag)
    {
        $this->isActive = $flag;
        return $this;
    }

    public function getIsActive()
    {
        return $this->isActive;
    }

    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function setComment($comment)
    {
        $this->comment = $comment;
        return $this;
    }

    public function getComment()
    {
        return $this->comment;
    }

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