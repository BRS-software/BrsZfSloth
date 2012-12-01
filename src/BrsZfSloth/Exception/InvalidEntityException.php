<?php

namespace BrsZfSloth\Exception;

use BrsZfSloth\Entity\Entity;
use BrsZfSloth\Definition\Field;

class InvalidEntityException extends \InvalidArgumentException implements ExceptionInterface {
    protected $_entity;

    public function __construct(Entity $entity, \InvalidArgumentException $prev) {
        $this->_entity = $entity;

//        if (null === $msg && null !== $prev)
//            $msg = $prev->getMessage();

        $message = sprintf('Model %s is invalid - %s', get_class($entity), $prev->getMessage());
        parent::__construct($message, 0, $prev);
    }

    public function getModel() {
        return $this->_entity;
    }
}