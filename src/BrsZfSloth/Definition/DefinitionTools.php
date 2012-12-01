<?php
namespace BrsZfSloth\Definition;

use BrsZfSloth\Exception;
use BrsZfSloth\Exception\ExceptionTools;

class DefinitionTools
{
    private static $transformUnderscoreToCamelCase;
    private static $transformCamelCaseToUnderscore;

    public static function transformUnderscoreToCamelCase($value)
    {
        if (null === self::$transformUnderscoreToCamelCase) {
            self::$transformUnderscoreToCamelCase = function($letters) {
                $letter = substr(array_shift($letters), 1, 1);
                return ucfirst($letter);
            };
        }
        return preg_replace_callback('/(_[a-z])/', self::$transformUnderscoreToCamelCase, $value);
    }

    public static function transformCamelCaseToUnderscore($value)
    {
        if (null === self::$transformCamelCaseToUnderscore) {
            self::$transformCamelCaseToUnderscore = function($letters) {
                $letter = array_shift($letters);
                return '_' . strtolower($letter);
            };
        }
        return preg_replace_callback('/([A-Z])/', self::$transformCamelCaseToUnderscore, $value);
    }

    public static function getDefinitionFromParam($definition)
    {
        if ($definition instanceof Definition) {
            return $definition;
        } elseif ($definition instanceof DefinitionAwareInterface) {
            return $definition->getDefinition();
        } else {
            try {
                return Definition::factory($definition);
            } catch (Exception\InvalidArgumentException $e) {
                throw new Exception\InvalidArgumentException(
                    ExceptionTools::msg('paramter must be Definitoin|DefinitionAwareInterface|DefinitionProviderInterface|array, given %s', $definition), 0, $e
                );
            }
        }
    }
}