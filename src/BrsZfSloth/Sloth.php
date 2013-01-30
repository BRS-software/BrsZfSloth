<?php

namespace BrsZfSloth;

use Zend\ServiceManager\ServiceManager;
use BrsZfSloth\Module\ModuleInterface;
use BrsZfSloth\Definition\Definition;

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

    // public static function registerModule(ModuleInterface $module, ServiceManager $serviceManager)
    // {
    //     self::getOptions()->addModule($module);

    //     foreach ($module->getDbTables() as $tableWithSchema) {
    //         list($tableName, $schema) = explode('.', $tableWithSchema);

    //         if ($schema === Definition::DEFAULT_SCHEMA) {
    //             $factoryName = $tableName . '.repository';
    //         } else {
    //             $factoryName = $tableWithSchema . '.repository';
    //         }

    //         $serviceManager->setFactory('tes.t', function($sm) use ($factoryName) {
    //             dbg($factoryName);
    //             return new \stdclass;
    //         });
    //     }
    // }
}