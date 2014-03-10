<?php
namespace BrsZfSloth\Hydrator\Strategy;

use Zend\Stdlib\Hydrator\Strategy\StrategyInterface;

class Float implements StrategyInterface
{
    /**
     * Converts the given value so that it can be extracted by the hydrator.
     *
     * @param mixed $value The original value.
     * @return mixed Returns the value that should be extracted.
     */
    public function extract($value)
    {
        if ($value !== null) {
            return (float) $value;
        }
        return null;
    }

    /**
     * Converts the given value so that it can be hydrated by the hydrator.
     *
     * @param mixed $value The original value.
     * @return mixed Returns the value that should be hydrated.
     */
    public function hydrate($value)
    {
        return (float) $value;
    }
}
