<?php
namespace BrsZfSloth\Repository;


use Zend\Stdlib\AbstractOptions;
// use Zend\Cache\Storage\Adapter\AbstractAdapter as CacheAdapter;
// use Zend\Cache\StorageFactory as CacheFactory;
use Zend\Db\Adapter\Adapter as DbAdapter;
use Zend\EventManager\EventManager;
use Zend\Cache\StorageFactory as CacheFactory;
use Zend\Cache\Storage\StorageInterface as CacheStorage;


use BrsZfSloth\Sloth;
use BrsZfSloth\Options as DefaultOptions;
use BrsZfSloth\Exception;
use BrsZfSloth\Definition\Definition;

class RepositoryOptions extends AbstractOptions
{
    protected $defaultOptions;
    protected $dbAdapter;
    protected $definition;
    protected $eventManager;
    protected $eventManagerClass;
    protected $caching; // enable/disable data cache
    protected $cache; // cache entities objects
    protected $clearCacheOnEvents = ['post.update', 'post.delete'];

    public function __construct($options = null, DefaultOptions $defaultOptions = null)
    {
        parent::__construct($options);
        if (null !== $defaultOptions) {
            $this->setDefaultOptions($defaultOptions);
        }
    }

    public function setDefaultOptions(DefaultOptions $defaultOptions)
    {
        $this->defaultOptions = $defaultOptions;
        return $this;
    }

    public function getDefaultOptions()
    {
        if (null === $this->defaultOptions) {
            $this->setDefaultOptions(Sloth::getOptions());
        }
        return $this->defaultOptions;
    }

    public function setDbAdapter(DbAdapter $adapter)
    {
        $this->dbAdapter = $adapter;
        return $this;
    }

    public function getDbAdapter()
    {
        if (null === $this->dbAdapter) {
            $this->setDbAdapter($this->getDefaultOptions()->getDefaultDbAdapter());
        }
        return $this->dbAdapter;
    }

    // name string, config array, Definition
    public function setDefinition($definition)
    {
        $this->definition = $definition;
        return $this;
    }

    public function getDefinition()
    {
        return $this->definition;
    }

    public function setEventManager(EventManager $manager)
    {
        $this->eventManager = $manager;
        return $this;
    }

    public function getEventManager()
    {
        if (null === $this->eventManager) {
            $emClass = $this->getEventManagerClass();
            $this->setEventManager(new $emClass);
        }
        return $this->eventManager;
    }

    public function setEventManagerClass($class)
    {
        if (! class_exists($class)) {
            throw new Exception\InvalidArgumentException(
                sprintf('class "%s" not exists', $class)
            );
        }
        $this->eventManagerClass = $class;
        return $this;
    }

    public function getEventManagerClass()
    {
        if (null === $this->eventManagerClass) {
            $this->setEventManagerClass($this->getDefaultOptions()->getDefaultEventManagerClass());
        }
        return $this->eventManagerClass;
    }

    public function setCache(CacheStorage $cache)
    {
        $this->cache = $cache;
        return $this;
    }

    public function getCache()
    {
        if (null === $this->cache) {
            $this->setCache(CacheFactory::factory(
                $this->getDefaultOptions()->getDataCacheConfig()
            ));
            $this->cache->setCaching($this->getCaching());
        }
        return $this->cache;
    }

    public function setCaching($flag)
    {
        $this->caching = (bool) $flag;
        return $this;
    }

    public function getCaching()
    {
        if (null === $this->caching) {
            $this->setCaching(
                $this->getDefaultOptions()->getDataCaching()
            );
        }
        return $this->caching;
    }

    public function setClearCacheOnEvents(array $events)
    {
        $this->clearCacheOnEvents = $events;
    }

    public function getClearCacheOnEvents()
    {
        return $this->clearCacheOnEvents;
    }
}
