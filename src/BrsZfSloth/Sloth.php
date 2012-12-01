<?php
namespace BrsZfSloth;

class Sloth
{
    private static $options;

    public static function reset()
    {
        self::$options = null;
    }

    public static function configure($options)
    {
        if ($options instanceof Options) {
            self::$options = $options;
        } elseif (is_array($options)) {
            self::$options = new Options;
            self::$options->setFromArray($options);
        } else {
            throw new Exception\InvalidArgumentException(
                'argument must be config array or Options object'
            );
        }
    }

    public static function getOptions()
    {
        if (null === self::$options) {
            self::configure(new Options);
        }
        return self::$options;
    }
}