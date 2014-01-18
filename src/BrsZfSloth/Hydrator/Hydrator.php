<?php
namespace BrsZfSloth\Hydrator;

use Zend\Stdlib\Hydrator\AbstractHydrator;

use BrsZfSloth\Exception;
use BrsZfSloth\Exception\ExceptionTools;
use BrsZfSloth\Definition\Definition;
use BrsZfSloth\Definition\DefinitionTools;
use BrsZfSloth\Entity\EntityTraitInterface;
use BrsZfSloth\Definition\DefinitionAwareInterface;

class Hydrator extends AbstractHydrator implements DefinitionAwareInterface
{
    protected $definition;
    protected $underscoreSeparatedKeys;

    public function __construct($underscoreSeparatedKeys = false)
    {
        parent::__construct();
        $this->setUnderscoreSeparatedKeys($underscoreSeparatedKeys);
    }

    public function setUnderscoreSeparatedKeys($flag)
    {
        $this->underscoreSeparatedKeys = (bool) $flag;
        return $this;
    }

    public function setDefinition(Definition $definition)
    {
        $this->definition = $definition;
    }

    public function getDefinition()
    {
        if (null === $this->definition) {
            throw new Exception\RuntimeException(
                'definition is not set in hydrator'
            );
        }
        return $this->definition;
    }

    /**
     * Extract values from an object
     *
     * @param  object $object
     * @return array
     */
    public function extract($object)
    {
        $this->assertEntity($object);
        $attributes = array();

        // for maximum performance
        if ($this->underscoreSeparatedKeys) {
            $attrKeyFn = function($field) {
                return $field->getMapping();
            };
        } else {
            $attrKeyFn = function($field) {
                return $field->getName();
            };
        }

        foreach ($this->getDefinition() as $field) {
            $method = 'get' . ucfirst($field->getName());

            // $value = $object->$method();
            // null values should not be extracted by added strategy
            // if (null === $value) {
            //     $value = 'null';
            // } else {
            //     $value = $this->extractValue($field->getName(), $object->$method());
            // }
            // $attributes[$field->getMapping()] = $value;

            $attrKey = $attrKeyFn($field);
            $attributes[$attrKey] = $this->extractValue($attrKey, $object->$method());
        }
        return $attributes;
    }

    /**
     * Hydrate $object with the provided $data.
     *
     * @param  array $data
     * @param  object $object
     * @return object
     */
    public function hydrate(array $data, $object)
    {
        $this->assertEntity($object);

        // TODO wheter it should set own definition in entity object?

        // for maximum performance loop is copied
        if ($this->underscoreSeparatedKeys) {
            foreach ($data as $property => $value) {
                $property = DefinitionTools::transformUnderscoreToCamelCase($property);
                // $method = 'set' . ucfirst($property);
                // $object->$method(
                //     $this->hydrateValue($property, $value)
                // );
                $this->_hydrate($object, $property, $value);

            }
        } else {
            foreach ($data as $property => $value) {
                $this->_hydrate($object, $property, $value);
                // $method = 'set' . ucfirst($property);
                // $object->$method(
                //     $this->hydrateValue($property, $value)
                // );
            }
        }
        return $this;
    }

    public function _hydrate($object, $property, $value)
    {
        $method = 'set' . ucfirst($property);
        $object->$method(
            null !== $value // do not hydrate null values
            ? $this->hydrateValue($property, $value)
            : null
        );
    }

    protected function assertEntity($object)
    {
        if (! $object instanceof EntityTraitInterface) {
            throw new Exception\InvalidArgumentException(
                sprintf('hydrator %s supports only entities instance of Brs\Zend\Sloht\Entity\EntityTraitInterface, given %s', get_class($this), is_object($object) ? get_class($object) : gettype($object))
            );
        }
    }
}