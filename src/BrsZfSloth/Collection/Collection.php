<?php

namespace BrsZfSloth\Collection;

use Countable;
use SeekableIterator;
use ArrayAccess;
use Serializable;
use Closure;

use BrsZfSloth\Exception;
use BrsZfSloth\Exception\ExceptionTools;
use BrsZfSloth\Definition\Definition;
use BrsZfSloth\Definition\DefinitionAwareInterface;
use BrsZfSloth\Repository\RepositoryInterface;
use BrsZfSloth\Repository\RepositoryAwareInterface;

class Collection implements
    RepositoryAwareInterface,
    DefinitionAwareInterface,
    Countable,
    SeekableIterator,
    ArrayAccess,
    Serializable
{
    protected $__repository;
    protected $__definition;
    protected $__allowedEntityClass;
    protected $__entities = array();
    protected $__totalCount;

    public function __construct()
    {
        foreach (func_get_args() as $arg) {
            $this[] = $arg;
        }
    }

    public function serialize()
    {
        return serialize([
            '__entities' => $this->__entities,
            '__allowedEntityClass' => $this->__allowedEntityClass,
            '__totalCount' => $this->__totalCount,
        ]);
    }

    public function unserialize($data)
    {
        // $this->data = unserialize($data);
        foreach (unserialize($data) as $property => $value) {
            $this->$property = $value;
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
                ExceptionTools::msg('repository not set in entity %s', $this)
            );
        }
        return $this->__repository;
    }

    public function setDefinition(Definition $definition)
    {
        $this->__definition = $definition;
        $this->setAllowedEntityClass(
            $definition->getOptions()->getEntityClass()
        );
        return $this;
    }

    public function getDefinition()
    {
        if (null === $this->__definition) {
            if (null !== $this->__repository) {
                $this->setDefinition($this->getRepository()->getDefinition());
            } else {
                throw new Exception\RuntimeException(
                    ExceptionTools::msg('definition not set in entity %s', $this)
                );
            }
        }
        return $this->__definition;
    }

    public function setAllowedEntityClass($class)
    {
        $this->__allowedEntityClass = $class;
        return $this;
    }

    public function getAllowedEntityClass()
    {
        if (null === $this->__allowedEntityClass) {
            throw new Exception\RuntimeException(
                ExceptionTools::msg('allowed entity class not set in collection $s', $this)
            );
        }
        return $this->__allowedEntityClass;
    }

    public function toArray($mapModelsToArray = false)
    {
        if ($mapModelsToArray) {
            return array_map(function ($v) use ($mapModelsToArray) {
                if ($mapModelsToArray instanceof Closure) {
                    return $mapModelsToArray($v);
                } else {
                    return $v->toArray();
                }
            }, $this->__entities);
        } else {
            return $this->__entities;
        }
    }

    public function isEmpty()
    {
        return 0 === $this->count();
    }

    public function isNotEmpty()
    {
        return ! $this->isEmpty();
    }

    public function setTotalCount($__totalCount)
    {
        $this->__totalCount = (int) $__totalCount;
        return $this;
    }

    public function issetTotalCount()
    {
        return null !== $this->__totalCount;
    }

    public function getTotalCount()
    {
        if (! $this->isset__totalCount()) {
            throw new Exception\RuntimeException(
                ExceptionTools::msg('total count not set in collection %s', $this)
            );
        }
        return $this->__totalCount;
    }

    public function getFirst()
    {
        if ($this->isEmpty()) {
            throw new Exception\EmptyCollectionException(
                'could not get first collection element, because collection is empty'
            );
        }
        // getting first element without reset collection pointer
        $keys = array_keys($this->__entities);
        return $this[reset($keys)];
    }

    public function getLast()
    {
        if ($this->isEmpty()) {
            throw new Exception\EmptyCollectionException(
                'could not get last collection element, because collection is empty'
            );
        }
        // getting last element without reset collection pointer
        $keys = array_keys($this->__entities);
        return $this[end($keys)];
    }

    public function reset()
    {
        $this->__entities = array();
        $this->__totalCount = null;
    }

    public function each(Closure $fn)
    {
        foreach ($this as $i => $model) {
            $fn($model, $i, $this);
        }
        return $this;
    }

    public function reverse()
    {
        $this->__entities = array_reverse($this->__entities);
        return $this;
    }

    /**
     * @see    Countable::count()
     * @return integer
     */
    public function count()
    {
        return count($this->__entities);
    }

    /**
     * @see    SeekableIterator::seek()
     * @param  integer $index
     */
    public function seek($index)
    {
        $this->rewind();
        $position = 0;

        while ($position < $index && $this->valid()) {
            $this->next();
            $position++;
        }

        if (! $this->valid()) {
            throw new Exception\OutOfBoundsException('Invalid seek position');
        }
    }

    /**
     * @see    SeekableIterator::current()
     * @return mixed
     */
    public function current()
    {
        return current($this->__entities);
    }

    /**
     * @see    SeekableIterator::next()
     * @return mixed
     */
    public function next()
    {
        return next($this->__entities);
    }

    /**
     * @see    SeekableIterator::key()
     * @return mixed
     */
    public function key()
    {
        return key($this->__entities);
    }

    /**
     * @see    SeekableIterator::valid()
     * @return boolean
     */
    public function valid()
    {
        return false !== $this->current();
    }

    /**
     * @see    SeekableIterator::rewind()
     */
    public function rewind()
    {
        reset($this->__entities);
    }

    /**
     * @see    ArrayAccess::offsetExists()
     * @param  mixed $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->__entities);
    }

    /**
     * @see    ArrayAccess::offsetGet()
     * @param  mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->__entities[$offset];
    }

    /**
     * @see    ArrayAccess::offsetSet()
     * @param  mixed $offset
     * @param  mixed $entity
     */
    public function offsetSet($offset, $entity)
    {
        if (! $entity instanceof $this->__allowedEntityClass) {
            throw new Exception\DomainException(
                sprintf('Entity is no instance of %s, %s given', $this->__allowedEntityClass, is_object($entity) ? get_class($entity) : gettype($entity))
            );
        }
        if ($offset === null) {
            $this->__entities[] = $entity;
        } else {
            $this->__entities[$offset] = $entity;
        }
    }

    /**
     * @see    ArrayAccess::offsetUnset()
     * @param  mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->__entities[$offset]);
    }
}