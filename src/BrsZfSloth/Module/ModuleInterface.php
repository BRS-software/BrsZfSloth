<?php

namespace BrsZfSloth\Module;

interface ModuleInterface
{
    public function getSlothModuleName();
    public function getDefinitionsPath();
    public function getDbTables();
}