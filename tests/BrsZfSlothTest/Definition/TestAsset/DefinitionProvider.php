<?php

namespace BrsZfSlothTest\Definition\TestAsset;

class DefinitionProvider implements \BrsZfSloth\Definition\DefinitionProviderInterface
{
    public static $definitionConfig = [];

    public function __construct(array $definitionConfig)
    {
        self::$definitionConfig = $definitionConfig;
    }

    public static function getDefinitionConfig()
    {
        return self::$definitionConfig;
    }
}