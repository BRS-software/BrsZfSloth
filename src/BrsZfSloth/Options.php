<?php
namespace BrsZfSloth;

use Zend\Stdlib\AbstractOptions;
use Zend\EventManager\EventManager;
use Zend\Db\Adapter\Adapter as DbAdapter;
use Zend\Cache\StorageFactory as CacheFactory;
use Zend\Cache\Storage\Adapter\AbstractAdapter as CacheAdapter;

use BrsZfSloth\Exception;

class Options extends AbstractOptions
{
    protected $discoverDefinitionsPaths = array();
    protected $defaultEntityClass = 'BrsZfSloth\Entity\Entity'; // or StdClass or AnyClass
    protected $defaultCollectionClass = 'BrsZfSloth\Collection\Collection';
    protected $defaultHydratorClass = 'BrsZfSloth\Hydrator\Hydrator'; // or 'Zend\Stdlib\Hydrator\*';
    protected $defaultDbAdapter;
    protected $defaultEventManagerClass = 'Zend\EventManager\EventManager';

    protected $dataCaching = false;
    protected $dataCacheConfig = [
        'adapter' => [
            'name' => 'apc',
            'options' => [
                // 'namespace' => 'sloth',
                'namespaceSeparator' => '|'
            ],
        ]
    ];

    protected $definitionCaching = false;
    protected $definitionCache;
    protected $definitionCacheConfig = [
        'adapter' => [
            'name' => 'apc',
            'options' => [
                // 'namespace' => 'sloth',
                'namespaceSeparator' => '|'
            ],
        ]
    ];

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
    }

    public function getDefinitionCaching()
    {
        return $this->definitionCaching;
    }

    public function getDefinitionCache()
    {
        if (null === $this->definitionCache) {
            $options = $this->getDefinitionCacheConfig();
            $options['adapter']['options']['namespace'] = join(
                array('BrsZfSloth', 'definitionCache'),
                $options['adapter']['options']['namespaceSeparator']
            );

            $this->definitionCache = CacheFactory::factory($options);
            $this->definitionCache->setCaching($this->definitionCaching);
        }
        return $this->definitionCache;
    }

    public function addDiscoverDefinitionsPath($path)
    {
        $this->discoverDefinitionsPaths[] = $path;
        return $this;
    }

    public function setDiscoverDefinitionsPaths(array $paths)
    {
        $this->discoverDefinitionsPaths = $paths;
        return $this;
    }

    public function getDiscoverDefinitionsPaths()
    {
        return $this->discoverDefinitionsPaths;
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
}
