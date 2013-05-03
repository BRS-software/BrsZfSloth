<?php

namespace BrsZfSloth\Hydrator\Strategy;

use StdClass;
use Zend\Stdlib\Hydrator\Strategy\StrategyInterface;

class Json implements StrategyInterface
{
    /**
     * Converts the given value so that it can be extracted by the hydrator.
     *
     * @param mixed $value The original value.
     * @return mixed Returns the value that should be extracted.
     */
    public function extract($value)
    {
        if (! $value instanceof StdClass) { // do not change not object values, that task for filter
            return $value;
        }
        return json_encode($value);
    }

    /**
     * Converts the given value so that it can be hydrated by the hydrator.
     *
     * @param mixed $value The original value.
     * @return mixed Returns the value that should be hydrated.
     */
    public function hydrate($value)
    {
        return json_decode($value);
    }
}
