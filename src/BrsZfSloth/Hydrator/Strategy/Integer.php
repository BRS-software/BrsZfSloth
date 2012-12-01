<?php
namespace BrsZfSloth\Hydrator\Strategy;

use Zend\Stdlib\Hydrator\Strategy\StrategyInterface;

class Integer implements StrategyInterface
{
    /**
     * Converts the given value so that it can be extracted by the hydrator.
     *
     * @param mixed $value The original value.
     * @return mixed Returns the value that should be extracted.
     */
    public function extract($value)
    {
        return $value;
    }

    /**
     * Converts the given value so that it can be hydrated by the hydrator.
     *
     * @param mixed $value The original value.
     * @return mixed Returns the value that should be hydrated.
     */
    public function hydrate($value)
    {
        // i.e myssql return int as '1'
        return is_numeric($value) ? (int) $value : $value;
    }
}
