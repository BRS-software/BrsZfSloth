<?php
namespace BrsZfSloth\Entity;

use Closure;

use Zend\Stdlib\Hydrator\HydratorInterface;

use BrsZfSloth\Exception;
use BrsZfSloth\Definition\Definition;
use BrsZfSloth\Definition\Field;
use BrsZfSloth\Hydrator\Hydrator as SlothHydrator;
use BrsZfSloth\Definition\DefinitionAwareInterface;

class EntityTools {

    private function __construct()
    {
    }

    public static function getDefinition($entity, $definition = null)
    {
        // TODO use DefinitionTool::getDefinitionForomParam()
        if ($definition instanceof Definition) {
            return $definition->assertEntityClass($entity);
        } elseif (null !== $definition) {
            return Definition::getCachedInstance($definition)->assertEntityClass($entity);
        } elseif ($entity instanceof DefinitionAwareInterface) {
            return $entity->getDefinition();
        } else {
            throw new Exception\RuntimeException(
                sprintf('could not specify definition for entity "%s"', is_object($entity) ? get_class($entity) : gettype($entity))
            );
        }
    }

    /**
     * @return array('changedKey' => array(new' => 'new_value'[, 'old' => 'old_value']))
     */
    public static function diff(array $newData, array $oldData)
    {
        $diff = array();
        foreach($newData as $key => $newValue) {
            if (array_key_exists($key, $oldData)) {
                if (null === $newValue || is_scalar($newValue) || is_array($newValue)) {
                    if ($oldData[$key] !== $newValue) {
                        $diff[$key] = [
                            'new' => $newValue,
                            'old' => $oldData[$key]
                        ];
                    }
                } else {
                    throw new Exception\UnsupportedException(
                        sprintf('Unsupported diff data type in key %s, type %s', $key, gettype($newValue))
                    );
                }
            } else {
                $diff[$key] = ['new' => $newValue];
            }
        }
        return $diff;
    }

    protected static function extract($entity, Closure $fn, $definition = null, HydratorInterface $hydrator = null)
    {
        $definition = self::getDefinition($entity, $definition);
        if (null === $hydrator) {
            $hydrator = $definition->getHydrator();
        }


        // // for performance, Sloth hydrator not necessary check fields
        // // without this "if" all must work without changes
        // // TODO UnitTest check performance
        // if ($hydrator instanceof SlothHydrator) {
        //     return $hydrator->extract($entity);
        // }

        // otherwise check is belong to definition (not existing keys will not be in the result array)

        // fetch values from some entity object
        $extract = $hydrator->extract($entity);

        // create full data array
        $outputValues = [];
        foreach ($definition->getFields() as $field) {
            if (isset($extract[$field->getName()])) {
                $extractedValue = $extract[$field->getName()];
            } elseif (isset($extract[$field->getMapping()])) {
                $extractedValue = $extract[$field->getMapping()];
            } elseif (null !== $field->getDefault()) {
                // apply default field value to entity
                // each value must be extracted by hydrator
                $extractedValue = $hydrator->extractValue(
                    $field->getName(),
                    $field->getDefault()
                );
            // not set fields are null
            } else {
                $extractedValue = null;
            }
            $fn($field, $extractedValue, $outputValues);
            // $values[$field->getMapping()] = $value;
        }
        return $outputValues;
    }

    public static function toArray($entity, $definition = null)
    {
        $definition = self::getDefinition($entity, $definition);

        // clone to remove strategies
        // $hydrator = clone $definition->getHydrator(); // not work, because the strategies is class ArrayObject and during cloning are not copied
        // $definition->removeHydratorStrategies($hydrator);

        $definition->removeHydratorStrategies(); // workaround cloning hydrator

        $array = self::extract($entity, function(Field $field, $extractedValue, &$outputValues) {
            $outputValues[$field->getName()] = $extractedValue;
        }, $definition);

        $definition->addHydratorStrategies(); // workaround cloning hydrator
        return $array;
    }

    public static function toRepository($entity, $definition = null)
    {
        return self::extract($entity, function(Field $field, $extractedValue, &$outputValues) {
            if (null === $extractedValue) {
                $outputValues[$field->getMapping()] = null;//'null'; // PDO somehow it would do
            } else {
                $outputValues[$field->getMapping()] = $extractedValue;
            }
        }, $definition);
    }

    public static function populate(array $values, $entity, $definition = null)
    {
        self::getDefinition($entity, $definition)
            ->getHydrator()
                ->hydrate($values, $entity)
        ;
        return $entity;
    }

    public static function setValue($fieldName, $value, $entity, $definition = null)
    {
        $definition = self::getDefinition($entity, $definition);
        $fieldName = $definition->getField((string) $fieldName)->getName(); // cast to string Field object
        $definition
            ->getHydrator()
                ->hydrate([$fieldName => $value], $entity)
        ;
        return $entity;
    }

    public static function getValue($fieldName, $entity, $definition = null)
    {
        $definition = self::getDefinition($entity, $definition);
        $values = self::toArray($entity, $definition);
        return $values[$definition->getField($fieldName)->getName()];
    }

    // TODO change method name to validateField
    public static function assertFieldValue($fieldName, $entity, $definition = null)
    {
        self::getDefinition($entity, $definition)->getField($fieldName)
            ->assertValue(
                self::getValue($fieldName, $entity, $definition)
            )
        ;
        return $entity;
    }

    public static function assertRequiredFields($entity, $definition = null)
    {
        $definition = self::getDefinition($entity, $definition);
        foreach ($definition->getNotNullFields() as $field) { // TODO change method name
            if (null === self::getValue($field->getName(), $entity, $definition)) {
                throw new Exception\FieldRequiredException(
                    $definition, $field, $entity
                );
            }
        }
        return $entity;
    }

    // TODO UnitTest
    public static function validate($entity, $definition = null)
    {
        $definition = self::getDefinition($entity, $definition);

        foreach (self::toArray($entity, $definition) as $fieldName => $value) {
            $field = $definition->getField($fieldName);

            // null the field value in not required fields will not be validate
            if (null !== $value || $field->isNotNull()) {
                $definition->assertValue($fieldName, $value);
                // continue;
            }
        }
        return $entity;
    }

    // TODO UnitTest
    public static function isValid($entity, $definition = null)
    {
        try {
            self::validate($entity, $definition);
            return true;
        } catch (Exception\InvalidFieldValueException $e) {
            return false;
        }
    }
}