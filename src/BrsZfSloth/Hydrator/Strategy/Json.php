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
        if (! is_array($value)) { // do not change not object values, that task for filter
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
        if (is_array($value)) {
            return $value; // XXX test why in this place not always is string?
        }
        $result = json_decode($value, true);
        if (null === $result && is_string($value)) {
            $result = json_decode('"' . $value . '"', true);
        }
        return $result;
    }
}
