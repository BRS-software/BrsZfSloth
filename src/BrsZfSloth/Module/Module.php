<?php

namespace BrsZfSloth\Module;

class Module implements ModuleInterface
{
    protected $name;
    protected $definitionsPath;
    protected $dbTables;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getSlothModuleName()
    {
        return $this->name;
    }

    public function setDefinitionsPath($path)
    {
        $this->definitionsPath = $path;
        return $this;
    }

    public function getDefinitionsPath()
    {
        return $this->definitionsPath;
    }

    public function setDbTables(array $tables)
    {
        $this->dbTables = array_unique($tables);
        return $this;
    }

    public function getDbTables()
    {
        return $this->dbTables;
    }
}