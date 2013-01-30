<?php

namespace BrsZfSloth\Collection;

use Countable;
use SeekableIterator;
use ArrayAccess;
use Serializable;

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

    // public function __sleep()
    // {
    //     return array(
    //         '__allowedEntityClass',
    //         '__entities',
    //         '__totalCount',
    //     );
    // }

    // public function __wakeup()
    // {
    //     mprd('wakeup');
    //     $this->connect();
    // }

    public function serialize()
    {
        // $this->__repository = null;
        // $this->__definition = null;
        // die(serialize($this->__entities));
        // mprd(serialize($this));
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

    public function toArray()
    {
        return $this->__entities;
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

    // public function arrayValues($field)
    // {
    //     $array = [];
    //     foreach ($this as $model) {
    //         $array[] = $model->$field; // XXX I think it should use hydrator
    //     }
    //     return $array;
    // }

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

    // public function save() {
    //     foreach ($this as $model) {
    //         $model->save();
    //     }
    //     return $this;
    // }

    // public function find($field, $value) {
    //     $called = get_called_class();
    //     $find = new $called;
    //     foreach ($this as $entity)
    //         if ($entity->$field === $value)
    //             $find[] = $entity;

    //     return $find;
    // }

    // public function delete() {
    //     array_walk_closure($this, function($item, $key) {
    //             return $item->delete();
    //         });
    //     $this->clear();
    //     return $this;
    // }


    // /**
    //  * Wykonuje dumpa obiektu. Ma możliwość rekurencyjnego wywoływania toArray()
    //  * na relacjach oraz posiada zabezpieczenie przez zapętlaniem się relacji.
    //  *
    //  * @param   integer $depth Poziom zagłębiania się w relacje
    //  * @param   array $callStack Stos wywołań
    //  * @return  array
    //  */
    // public function dump($depth = 1, array $callStack = array()) {
    //     $reqursion = true;

    //     $callStack[] = get_class($this);
    //     // zabezpieczenie przed requrencją jest w modelach, tu raczej nie ma
    //     // takiej potrzeby
    //     // sprawdzenie głębokości rozwijania
    //     if ((int) $depth < count($callStack))
    //         $reqursion = false;

    //     $result = array();
    //     foreach ($this as $k => $entity) {
    //         $value = $entity;
    //         if ($reqursion && is_object($value)) {
    //             if (method_exists($entity, 'dump'))
    //                 $value = $entity->dump($depth, $callStack);
    //             elseif (method_exists($entity, 'toArray'))
    //                 $value = $entity->toArray();
    //         }
    //         $result[$k] = $value;
    //     }
    //     return $result;
    // }

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
                sprintf('Entity is no instance of %s, %s given', $this->__allowedEntityClass, get_class($entity))
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
