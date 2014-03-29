<?php
namespace BrsZfSloth\Definition;

use Zend\Stdlib\AbstractOptions;
use Zend\Stdlib\Hydrator\HydratorInterface;

use BrsZfSloth\Sloth;
use BrsZfSloth\Assert;
use BrsZfSloth\Exception;
use BrsZfSloth\Options as DefaultOptions;

class DefinitionOptions extends AbstractOptions
{
    protected $defaultOptions;
    protected $entityClass;
    protected $collectionClass;
    protected $hydratorClass;
    protected $hydrator;
    protected $defaultOrder = array('id' => SORT_ASC);
    protected $uniqueKeys = [];
    protected $isTableUpdatable = true;

    public function __construct($options = null, DefaultOptions $defaultOptions = null)
    {
        parent::__construct($options);
        if (null !== $defaultOptions) {
            $this->setDefaultOptions($defaultOptions);
        }
    }

    public function setDefaultOptions(DefaultOptions $defaultOptions)
    {
        $this->defaultOptions = $defaultOptions;
        return $this;
    }

    public function getDefaultOptions()
    {
        if (null === $this->defaultOptions) {
            $this->setDefaultOptions(Sloth::getOptions());
        }
        return $this->defaultOptions;
    }

    public function setEntityClass($class)
    {
        if (! class_exists($class)) {
            throw new Exception\InvalidArgumentException(
                sprintf('argument must be valid class, given "%s"', $class)
            );
        }
        $this->entityClass = $class;
        return $this;
    }

    public function getEntityClass()
    {
        if (null === $this->entityClass) {
            $this->setEntityClass(
                $this->getDefaultOptions()->getDefaultEntityClass()
            );
        }
        return $this->entityClass;
    }

    public function setCollectionClass($class)
    {
        if (! class_exists($class) || ! in_array('ArrayAccess', class_implements($class))) {
            throw new Exception\InvalidArgumentException(
                sprintf('argument must be valid class and implements ArrayAccess interface, given "%s"', $class)
            );
        }
        $this->collectionClass = $class;
        return $this;
    }

    public function getCollectionClass()
    {
        if (null === $this->collectionClass) {
            $this->setCollectionClass(
                $this->getDefaultOptions()->getDefaultCollectionClass()
            );
        }
        return $this->collectionClass;
    }

    public function setHydratorClass($class)
    {
        if (! class_exists($class)) {
            throw new Exception\InvalidArgumentException(
                sprintf('argument must be valid class, given "%s"', $class)
            );
        }
        $this->hydratorClass = $class;
        $this->hydrator = null;
        return $this;
    }

    public function getHydratorClass()
    {
        if (null === $this->hydratorClass) {
            $this->setHydratorClass(
                $this->getDefaultOptions()->getDefaultHydratorClass()
            );
        }
        return $this->hydratorClass;
    }

    public function setHydrator(HydratorInterface $hydrator)
    {
        $this->hydrator = $hydrator;
        return $this;
    }

    public function getHydrator()
    {
        if (null === $this->hydrator) {
            $hydratorClass = $this->getHydratorClass();

            // hack, because this hydtrator does not have setter for underscoreSeparatedKeys...
            if ('Zend\Stdlib\Hydrator\ClassMethods' === $hydratorClass) {
                $this->setHydrator(new $hydratorClass(false));
            } else {
                $this->setHydrator(new $hydratorClass);
            }


        }
        return $this->hydrator;
    }

    public function setDefaultOrder(array $order)
    {
        $this->defaultOrder = $order;
        return $this;
    }

    public function getDefaultOrder()
    {
        return $this->defaultOrder;
    }

    public function setUniqueKeys(array $uniqueKeys)
    {
        $this->uniqueKeys = $uniqueKeys;
        return $this;
    }

    public function getUniqueKeys()
    {
        return $this->uniqueKeys;
    }

    public function hasUniqueKey($name)
    {
        return array_key_exists($name, $this->getUniqueKeys());
    }

    public function getUniqueKey($name)
    {
        if (! $this->hasUniqueKey($name)) {
            throw new Exception\OutOfBoundsException(
                sprintf('unique key %s does not defined in definition', $name)
            );
        }
        return Assert::notEmpty($this->getUniqueKeys()[$name]);
    }

    public function setIsTableUpdatable($isTableUpdatable)
    {
        $this->isTableUpdatable = (bool) $isTableUpdatable;
        return $this;
    }

    public function getIsTableUpdatable()
    {
        return $this->isTableUpdatable;
    }
}