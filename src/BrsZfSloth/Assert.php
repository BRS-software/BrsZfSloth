<?php
namespace BrsZfSloth;

use BrsZfSloth\Exception\AssertException;

class Assert
{
    public static function objectClass($value, $class)
    {
        if (! $value instanceof $class) {
            throw new AssertException(
                sprintf('value %s is not instance of %s', self::valueToString($value), $class)
            );
        }
        return $value;
    }

    public static function characterVarying($value, $max)
    {
        return self::string($value, 0, $max);
    }

    public static function string($value, $min = 0, $max = null, $errorMessage = 'value %s has an invalid length (%s-%s)')
    {
        if (! is_string($value)) {
            throw new AssertException(
                sprintf('value %s is not a string', self::valueToString($value))
            );
        }
        $length = strlen($value);

        if ($min > $length) {
            throw new AssertException(
                sprintf($errorMessage, $value, $min, $max)
            );
        } elseif (null !== $max && $max < $length) {
            throw new AssertException(
                sprintf($errorMessage, $value, $min, $max)
            );
        }
    }

    public static function int($value, $errorMessage = 'value %s is not a integer')
    {
        if (! is_int($value)) {
            throw new AssertException(
                sprintf($errorMessage, self::valueToString($value))
            );
        }
        return $value;
    }

    // alias
    public static function integer($value, $errorMessage = 'value %s is not a integer')
    {
        return self::int($value, $errorMessage);
    }

    public static function smallint($value)
    {
        return self::numeric($value, -32768, 32767);
    }

    public static function float($value, $min = null, $max = null, $rangeType = '<>', $errorMessage = 'value %s is not a float or outside the range %s') {
        if (! is_float($value)) {
            throw new AssertException(
                sprintf($errorMessage, self::valueToString($value), $min, $max, $rangeType)
            );
        }
        return self::numeric($value, $min, $max, $rangeType, $errorMessage);
    }

    /**
     * @param numeric $value
     * @param numeric $min
     * @param numeric $max
     * @param string $rangeType
     *      <> - closed on both sides
     *      () - both sides open
     *      <) - left closed
     *      (> - right closed
     * @param string $errorMessage
     * @return numeric
     */
    public static function numeric($value, $min = null, $max = null, $rangeType = '<>', $errorMessage = 'value %s is not a numeric or outside the range %s')
    {
        $m = function() use ($errorMessage, $value, $min, $max, $rangeType) {
            $range = sprintf('%s%s - %s%s', substr($rangeType, 0, 1), $min, $max, substr($rangeType, 1));
            return sprintf($errorMessage, self::valueToString($value), $range);
        };

        if (! is_numeric($value)) {
            throw new AssertException($m());
        }

        if (null === $min && null === $max) {
            return $value;
        }

        self::inArray($rangeType, array('<>', '()', '<)', '(>'));

        // passed min
        if (null !== $min && null === $max) {
            $r = substr($rangeType, 0, 1);
            if ('<' == $r && ! ($min <= $value))    throw new AssertException($m());
            elseif ('(' == $r && ! ($min < $value)) throw new AssertException($m());

        // passed max
        } elseif (null === $min && null !== $max) {
            $r = substr($rangeType, 1, 1);
            if ('>' == $r && ! ($max >= $value))    throw new AssertException($m());
            elseif (')' == $r && ! ($min > $value)) throw new AssertException($m());

        // passed min and max
        } else {
            if ('<>' == $rangeType && ! ($min <= $value && $max >= $value))     throw new AssertException($m());
            elseif ('()' == $rangeType && !($min < $value && $max > $value))    throw new AssertException($m());
            elseif ('<)' == $rangeType && !($min <= $value && $max > $value))   throw new AssertException($m());
            elseif ('(>' == $rangeType && !($min < $value && $max >= $value))   throw new AssertException($m());
        }
        return $value;
    }

    public static function bool($value, $errorMessage = 'value %s is not a boolean')
    {
        if (! is_bool($value)) {
            throw new AssertException(
                sprintf($errorMessage, self::valueToString($value))
            );
        }
        return $value;
    }

    public static function date($value, $format= 'Y-m-d', $message = 'value %s is not a date in format %s')
    {
        // XXX remove microtime
        $value = explode('.', $value)[0];
        $format = str_replace('.u', '', $format);

        $time = strtotime($value);
        if (false === $time || date($format, $time) !== $value) {
            throw new AssertException(
                sprintf($message, self::valueToString($value), $format)
            );
        }
        return $value;
    }

    public static function inArray($value, array $array, $message = 'Value %s dosen\'t exists in %s')
    {
        if (! in_array($value, $array, true)) {
            throw new AssertException(sprintf($message, self::valueToString($value), var_export(array_values($array), true)));
        }
        return $value;
    }

    public static function arra($value, $message = 'Value %s is not a array')
    {
        if (! is_array($value)) {
            throw new AssertException(sprintf($message, self::valueToString($value)));
        }
        return $value;
    }

    public static function has($value, $keys, $message = 'element does not have key "%s"')
    {
        $test = (array) $value;
        foreach ((array) $keys as $k) {
            if (! array_key_exists($k, $test)) {
                throw new AssertException(
                    // sprintf($message, self::valueToString($value), $k)
                    sprintf($message, $k)
                );
            }
        }
        return $value;
    }

    public static function valueToString($value)
    {
        $type = gettype($value);
        $preview = '';

        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif (is_array($value)) {
            $value = count($value);
        } elseif (is_string($value)) {
            $preview = $value;
            $value = strlen($value);
        } elseif (is_object($value)) {
            $value = get_class($value);
        } elseif (null === $value) {
            return 'null';
        }
        return sprintf('%s(%s)%s', $type, $value, $preview);
    }
}