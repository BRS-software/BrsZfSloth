<?php
namespace BrsZfSloth;

use Closure;
use __;

use Zend\Stdlib\AbstractOptions;
use Zend\EventManager\EventManager;
use Zend\Db\Adapter\Adapter as DbAdapter;
use Zend\Cache\StorageFactory as CacheFactory;
use Zend\Cache\Storage\Adapter\AbstractAdapter as CacheAdapter;
use Zend\ServiceManager\ServiceManager;

use BrsZfSloth\Exception;
use BrsZfSloth\Definition\Definition;
use BrsZfSloth\Module\ModuleInterface;

class Options extends AbstractOptions
{
    protected $defaultEntityClass = 'BrsZfSloth\Entity\Entity'; // or StdClass or AnyClass
    protected $defaultCollectionClass = 'BrsZfSloth\Collection\Collection';
    protected $defaultHydratorClass = 'BrsZfSloth\Hydrator\Hydrator'; // or 'Zend\Stdlib\Hydrator\*';
    protected $defaultDbAdapter;
    protected $defaultEventManagerClass = 'Zend\EventManager\EventManager';
    protected $defaultServiceManager;

    protected $dataCaching = false;
    protected $dataCacheConfig = [
        // 'adapter' => [
        //     'name' => 'filesystem',
        //     'options' => [
        //         'cacheDir' => 'data/cache',
        //         'dirPermission' => 0755,
        //         'filePermission' => 0664,
        //         // 'namespace' => 'sloth',
        //         // 'namespaceSeparator' => '|'
        //     ],
        // ]
        'adapter' => [
            'name' => 'apc',
            'options' => [
                // 'namespace' => 'sloth',
                // 'namespaceSeparator' => '|'
            ],
        ]
    ];

    protected $definitionsPaths = array();
    protected $definitionCaching = false;
    protected $definitionCache;
    protected $definitionCacheConfig = [
        'adapter' => [
            'name' => 'apc',
            'options' => [
                'namespace' => 'BrsZfSloth|DefinitionCache',
                // 'namespace' => 'sloth',
                // 'namespaceSeparator' => '|'
            ],
        ]
    ];

    protected $definitionGeneratorIgnoredDbTables = [];
    protected $modules = [];

    public function setDataCacheConfig(array $config)
    {
        $this->dataCacheConfig = $config;
        return $this;
    }

    public function getDataCacheConfig()
    {
        return $this->dataCacheConfig;
    }

    public function setDefinitionCacheConfig(array $config)
    {
        $this->definitionCacheConfig = $config;
        return $this;
    }
    public function getDefinitionCacheConfig()
    {
        return $this->definitionCacheConfig;
    }

    public function setDefinitionCaching($flag)
    {
        $this->definitionCaching = (bool) $flag;
        return $this;
    }

    public function getDefinitionCaching()
    {
        return $this->definitionCaching;
    }

    public function getDefinitionCache()
    {
        if (null === $this->definitionCache) {
            $options = $this->getDefinitionCacheConfig();

            $this->definitionCache = CacheFactory::factory($options);
            $this->definitionCache->setCaching($this->getDefinitionCaching());
        }
        return $this->definitionCache;
    }

    public function addDefinitionsPath($path)
    {
        // $this->definitionsPaths[] = realpath($path);
        $this->definitionsPaths[] = $path;
        $this->definitionsPaths = array_unique($this->definitionsPaths);
        return $this;
    }

    public function setDefinitionsPaths(array $paths)
    {
        $this->definitionsPaths = [];
        array_walk($paths, function($v) {
            $this->addDefinitionsPath($v);
        });
        return $this;
    }

    public function getDefinitionsPaths($addModulesPath = false)
    {
        $paths = $this->definitionsPaths;

        // search also in modules
        foreach ($this->getModules() as $module) {
            $paths[] = $module->getDefinitionsPath();
        }

        if (empty($paths)) {
            throw new Exception\NotSetException('No set any definition path');
        }
        return array_unique($paths);
    }

    public function setModules(array $modules)
    {
        array_walk($modules, function($module) {
            $this->addModule($module);
        });
        return $this;
    }

    public function addModule(ModuleInterface $module)
    {
        if (array_key_exists($module->getSlothModuleName(), $this->modules)) {
            throw new Exception\RuntimeException(
                sprintf('module %s already exists', $module->getSlothModuleName())
            );
        }
        $this->modules[$module->getSlothModuleName()] = $module;
        return $this;
    }

    public function getModules()
    {
        return $this->modules;
    }

    public function setDefaultDbAdapter(DbAdapter $adapter)
    {
        $this->defaultDbAdapter = $adapter;
        return $this;
    }

    public function getDefaultDbAdapter()
    {
        if (! $this->defaultDbAdapter instanceof DbAdapter) {
            throw new Exception\NotSetException('Default db adapter not was defined');
        }
        return $this->defaultDbAdapter;
    }

    // enable disable data cache
    public function setDataCaching($flag)
    {
        $this->dataCaching = (bool) $flag;
        return $this;
    }

    public function getDataCaching()
    {
        return $this->dataCaching;
    }

    public function setDefaultEntityClass($class)
    {
        if (! class_exists($class)) {
            throw new Exception\InvalidArgumentException(
                sprintf('class "%s" not exists', $class)
            );
        }
        $this->defaultEntityClass = $class;
        return $this;
    }

    public function getDefaultEntityClass()
    {
        return $this->defaultEntityClass;
    }

    public function setDefaultServiceManager(ServiceManager $serviceManager)
    {
        $this->defaultServiceManager = $serviceManager;
        return $this;
    }

    public function getDefaultServiceManager()
    {
        if (null === $this->defaultServiceManager) {
            throw new Exception\RuntimeException(
                'default service manager not set'
            );
        }
        return $this->defaultServiceManager;
    }

    public function setDefaultCollectionClass($class)
    {
        if (! class_exists($class) || ! in_array('ArrayAccess', class_implements($class))) {
            throw new Exception\InvalidArgumentException(
                sprintf('class "%s" not exists or not implements ArrayAccess interface', $class)
            );
        }
        $this->defaultCollectionClass = $class;
        return $this;
    }

    public function getDefaultCollectionClass()
    {
        return $this->defaultCollectionClass;
    }

    public function setDefaultHydratorClass($class)
    {
        if (! class_exists($class)) {
            throw new Exception\InvalidArgumentException(
                sprintf('class "%s" not exists', $class)
            );
        }
        $this->defaultHydratorClass = $class;
        return $this;
    }

    public function getDefaultHydratorClass()
    {
        return $this->defaultHydratorClass;
    }

    public function setDefaultEventManagerClass($class)
    {
        if (! class_exists($class)) {
            throw new Exception\InvalidArgumentException(
                sprintf('class "%s" not exists', $class)
            );
        }
        $this->defaultEventManagerClass = $class;
        return $this;
    }

    public function getDefaultEventManagerClass()
    {
        return $this->defaultEventManagerClass;
    }

    public function addDefinitionGeneratorIgnoredDbTable($table)
    {
        if (false === strpos($table, '.')) {
            $table = Definition::DEFAULT_SCHEMA . '.' . $table;
        }
        $this->definitionGeneratorIgnoredDbTables[] = $table;
        $this->definitionGeneratorIgnoredDbTables = array_unique($this->definitionGeneratorIgnoredDbTables);
        return $this;
    }

    public function setDefinitionGeneratorIgnoredDbTables(array $tables)
    {
        array_walk($tables, function($table) {
            $this->addDefinitionGeneratorIgnoredDbTable($table);
        });
        return $this;
    }

    public function getDefinitionGeneratorIgnoredDbTables()
    {
        $modulesTables = [];
        array_walk($this->getModules(), function($module) use (&$modulesTables) {
            $modulesTables = array_merge($modulesTables, $module->getDbTables());
        });

        return array_unique(array_merge(
            $this->definitionGeneratorIgnoredDbTables,
            $modulesTables
        ));
    }
}
