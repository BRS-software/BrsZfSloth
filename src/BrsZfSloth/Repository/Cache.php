<?php
namespace BrsZfSloth\Repository;

// use Zend\Cache\StorageFactory as CacheFactory;
use Zend\Cache\Storage\StorageInterface as CacheStorage;
use Zend\Cache\Storage\FlushableInterface as CacheFlushable;
use Zend\Cache\Storage\ClearByNamespaceInterface as CacheClearByNamespace;

use BrsZfSloth\Sloth;
use BrsZfSloth\Event;
use BrsZfSloth\Exception;
use BrsZfSloth\Exception\ExceptionTools;

class Cache
{
    protected $storage;
    protected $namespace;

    public static function factory(Repository $repository)
    {
        // mprd($repository);
        $cache = new self(
            $repository->getOptions()->getCache(),
            [$repository->getDsn(), get_class($repository)]
        );
        // mprd($repository->getOptions()->getClearCacheOnEvents());
        foreach ($repository->getOptions()->getClearCacheOnEvents() as $event) {
            $repository->getEventManager()->attach($event, function($e) use ($cache) {
                $cache->clearStorage();
            });
        }
        // $repository->getEventManager()->attach('post.update', $cache);

        return $cache;
    }

    public function __construct(CacheStorage $storage, $namespace)
    {
        $this->storage = $storage;
        $this->namespace = join($this->storage->getOptions()->getNamespaceSeparator(), (array) $namespace);
        $this->storage->getOptions()->setNamespace($this->getNamespace());
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    public function getCacheId(/*[arg1, argN]*/)
    {
        $args = func_get_args();
        array_unshift($args, $this->getNamespace());
        return join($args, $this->storage->getOptions()->getNamespaceSeparator());
    }

    public function getStorage()
    {
        return $this->storage;
    }

    public function clearStorage()
    {
        if ($this->storage instanceof CacheClearByNamespace) {
            $this->storage->clearByNamespace($this->namespace);
        } elseif ($this->storage instanceof CacheFlushable) {
            $this->storage->flush();
        } else {
            throw new Exception\RuntimeException(
                'cache adapter could not be flushed'
            );
        }
    }
}