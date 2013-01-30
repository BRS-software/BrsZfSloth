<?php

namespace BrsZfSloth\Entity;

use Zend\ServiceManager\ServiceManager;
use Brs\Zf\ServiceManager\ServiceManagerAwareTrait;
use BrsZfSloth\Exception;
use BrsZfSloth\Exception\ExceptionTools;
use BrsZfSloth\Definition\Definition;
use BrsZfSloth\Repository\RepositoryInterface;

trait EntityTrait
{
    use ServiceManagerAwareTrait;

    protected $__values = array();
    protected $__originValues = array();
    // protected $__isChanged = false;
    protected $__repository;
    protected $__definition;
    // protected $__serviceManager;

    public function __call($method, $args)
    {
        try {
            $field = $this->getDefinition()->getField(
                lcfirst(substr($method, 3)) // remove set|get
            );

        } catch (Exception\OutOfBoundsException $e) {
            throw new Exception\BadMethodCallException(
                ExceptionTools::msg("method %s::%s() not exists", get_class($this), $method),
                0, $e
            );
        }

        switch(substr($method, 0, 3)) {
            case 'set':
                $this->__values[$field->getName()] = $args[0];
                // $this->__isChanged = true;
                return $this;

            case 'get':
                if (! array_key_exists($field->getName(), $this->__values)) {
                    return $field->getDefault();
                }
                return $this->__values[$field->getName()];
        }
    }

    // public function setServiceManager(ServiceManager $serviceManager)
    // {
    //     $this->__serviceManager = $serviceManager;
    //     return $this;
    // }

    // public function getServiceManager()
    // {
    //     if (null === $this->__serviceManager) {
    //         throw new Exception\RuntimeException(
    //             ExceptionTools::msg('service manager not set in entity %s', get_class($this))
    //         );
    //     }
    //     return $this->__serviceManager;
    // }

    public function serialize()
    {
        $data = [
            '__values' => $this->__values,
            '__originValues' => $this->__originValues,
        ];
        if (null !== $this->__definition) {
            $data['__definition'] = $this->__definition->comesFromFile()
                ? $this->__definition->getName()
                : $this->__definition;
        }
        return serialize($data);
    }

    public function unserialize($data)
    {
        //dbgd(unserialize($data), 'unserialize');
        $data = unserialize($data);
        $this->__values = $data['__values'];
        $this->__originValues = $data['__originValues'];

        if (isset($data['__definition'])) {
            $this->__definition = is_string($data['__definition'])
                ? Definition::getCachedInstance($data['__definition'])
                : $data['__definition'];
        }
    }

    public function setRepository(RepositoryInterface $repository)
    {
        $this->__repository = $repository;
        return $this;
    }

    public function getRepository()
    {
        if (null === $this->__repository) {
            throw new Exception\RuntimeException(
                ExceptionTools::msg('repository not set in entity %s', get_class($this))
            );
        }
        return $this->__repository;
    }

    public function setDefinition(Definition $definition)
    {
        $this->__definition = $definition;
        return $this;
    }

    public function getDefinition()
    {
        if (null === $this->__definition) {
            if (null !== $this->__repository) {
                $this->setDefinition($this->getRepository()->getDefinition());
            } else {
                throw new Exception\RuntimeException(
                    ExceptionTools::msg('definition not set in entity %s', get_class($this))
                );
            }
        }
        return $this->__definition;
    }

    public function markAsOrigin(array $originValues = null)
    {
        $this->__originValues = $originValues ?: $this->toArray();
        // $this->__isChanged = false;
        return $this;
    }

    public function getOriginValues()
    {
        return $this->__originValues;
    }

    public function getChanges()
    {
        // performance feature: early changes detect
        // if (! $this->__isChanged) {
        //     return array();
        // }
        return EntityTools::diff($this->toArray(), $this->getOriginValues());
    }

    public function populate(array $values)
    {
        EntityTools::populate($values, $this);
        return $this;
    }

    public function toArray()
    {
        return EntityTools::toArray($this);
    }

    public function save()
    {
        $this->getRepository()->save($this);
        return $this;
    }

    public function delete()
    {
        $this->getRepository()->delete($this);
        return $this;
    }
}