<?php
namespace BrsZfSloth\Hydrator\Strategy;

use Zend\Stdlib\Hydrator\Strategy\StrategyInterface;

// use BrsZfSloth\Exception;
// use BrsZfSloth\Entity\Entity;
// use BrsZfSloth\Definition\DefinitionAwareInterface;

class Boolean implements StrategyInterface
{
    /**
     * Converts the given value so that it can be extracted by the hydrator.
     *
     * @param mixed $value The original value.
     * @return mixed Returns the value that should be extracted.
     */
    public function extract($value)
    {
        if (! is_bool($value)) { // do not change not boolean values, that task for filter
            return $value;
        }
        // return $value ? 'true' : 'false'; // mysql does not understand
        return (int) $value;
    }

    /**
     * Converts the given value so that it can be hydrated by the hydrator.
     *
     * @param mixed $value The original value.
     * @return mixed Returns the value that should be hydrated.
     */
    public function hydrate($value)
    {
        return (bool) $value;
    }
}
