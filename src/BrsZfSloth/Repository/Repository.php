<?php
namespace BrsZfSloth\Repository;

use PDO;
use Closure;
use PDOException;

use Zend\Db\Exception\ExceptionInterface as DbException;
use Zend\EventManager\EventManager;
use Zend\Db\Adapter\Adapter as DbAdapter;
use Zend\Db\Sql as ZfSql;
use Zend\Db\Sql\Predicate\PredicateInterface;
use Zend\ServiceManager\ServiceManagerAwareInterface;
// use Zend\Cache\StorageFactory as CacheFactory;

use BrsZfSloth\Sloth;
use BrsZfSloth\Sql\Expr;
use BrsZfSloth\Sql;
use BrsZfSloth\Event;
use BrsZfSloth\Exception;
use BrsZfSloth\Exception\ExceptionTools;
use BrsZfSloth\Sql\Order;
use BrsZfSloth\Sql\Where;
use BrsZfSloth\Entity\Entity;
use BrsZfSloth\Entity\EntityTools;
use BrsZfSloth\Entity\Feature\OriginValuesFeatureInterface;
use BrsZfSloth\Entity\Feature\GetChangesFeatureInterface;
use BrsZfSloth\Collection\Collection;
use BrsZfSloth\Definition\Definition;
use BrsZfSloth\Definition\DefinitionAwareInterface;
use BrsZfSloth\Event\EntityEventArgs;
use BrsZfSloth\Event\EntityAndChangesEventArgs;

class Repository implements RepositoryInterface
{
    private static $instances = array();

    protected $definitionName;
    protected $definition;
    protected $options;
    protected $adapter;
    protected $eventManager;
    protected $cache;

    public static function getInstance()
    {
        $class = get_called_class();
        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new $class();
        }
        return self::$instances[$class];
    }

    public static function reset()
    {
        self::$instances = array();
    }

    public function __construct($options = null)
    {
        if ($options instanceof RepositoryOptions) {
            $this->options = clone $options;
        } elseif (is_array($options)) {
            $this->options = new RepositoryOptions($options);
        } elseif (null === $options) {
            $this->options = new RepositoryOptions;
        } else {
            throw new Exception\InvalidArgumentException(
                'argument must be object instance of Definition\Options or config options array or null'
            );
        }

        if ($this->options->getDefinition() instanceof Definition) {
            $this->definition = $this->options->getDefinition();
        } else {
            $this->definition = Definition::getCachedInstance($this->options->getDefinition() ?: $this->getDefinitionName());
        }
        $this->adapter = $this->options->getDbAdapter();
        $this->eventManager = $this->options->getEventManager();
        $this->cache = Cache::factory($this);
    }

    public function __toString()
    {
        // return sprintf('%s (%s)', get_class($this), $this->getDsn());
        return $this->getDsn();
    }

    public function getOptions()
    {
        return $this->options;
    }

    /**
     * DefinitionAwareInterface
     */
    public function setDefinition(Definition $definition)
    {
        $this->definition = $definition;
        return $this;
    }

    /**
     * DefinitionAwareInterface
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    public function getDefinitionName()
    {
        if (null === $this->definitionName) {
            throw new Exception\RuntimeException(
                'definition name not defined in ' . get_class($this)
            );
        }
        return $this->definitionName;
    }

    public function getAdapter()
    {
        return $this->adapter;
    }

    public function getDsn()
    {
        return $this->getAdapter()->getDriver()->getConnection()->getConnectionParameters()['dsn'];
    }

    public function beginTransaction()
    {
        $this->getAdapter()->getDriver()->getConnection()->beginTransaction();
        return $this;
    }

    public function rollback()
    {
        $this->getAdapter()->getDriver()->getConnection()->rollback();
        return $this;
    }

    public function commit()
    {
        $this->getAdapter()->getDriver()->getConnection()->commit();
        return $this;
    }

    public function getEventManager()
    {
        return $this->eventManager;
    }

    public function getCache()
    {
        return $this->cache;
    }

    public function insert($entity)
    {
        $this->definition->assertEntityClass($entity);
        EntityTools::validate($entity, $this->getDefinition());
        $entityValues = EntityTools::toRepository($entity, $this->getDefinition());

        // remove null values to trigger default values in db
        foreach ($entityValues as $k => $v) {
            if (null === $v) {
                unset($entityValues[$k]);
            }
        }

        $event = new Event\EntityOperation($this, $entity);
        $this->eventManager->trigger('pre.insert', $event);

        $statment = $this->adapter->createStatement();
        $insert = (new ZfSql\Insert($this->getDefinition()->getTable()))
            ->values($entityValues)
        ;
        $insert->prepareStatement($this->adapter, $statment);

        try {
            $affected = $statment->execute()->getAffectedRows();

        } catch (DbException $e) {
            $this->eventManager->trigger('fail.insert', $event->setParam('exception', $e));

            // catch unique violation exceptions
            $previous = $e->getPrevious();
            if ($previous instanceof PDOException && $previous->getCode() == 23505) {
                throw new Exception\DuplicateKeyException($previous->getMessage(), $previous->getCode(), $previous);
            }

            throw new Exception\StatementException($insert->getSqlString(), 0, $e);
        }

        // get last insert id
        $lastId = $this->adapter->getDriver()->getConnection()->getLastGeneratedValue(
            $this->definition->getLastGeneratedValueParam()
        );

        // set id value in entity
        EntityTools::setValue(
            $this->definition->getPrimary(),
            $lastId,
            $entity,
            $this->definition
        );
        // mprd(EntityTools::toArray($entity, $this->getDefinition()));

        if ($entity instanceof OriginValuesFeatureInterface) {
            $entity->markAsOrigin();
        }

        $this->eventManager->trigger('pre.insert', $event);

        return $lastId;
    }

    /**
     * @param Entity $entity
     * @return integer Affected rows
     * @throws Exception\NotAffectedException
     * @throws Exception\AmbiguousException
     */
    public function update($entity)
    {
        $this->definition->assertEntityClass($entity);
        EntityTools::validate($entity, $this->getDefinition());
        $entityValues = EntityTools::toRepository($entity, $this->getDefinition());

        if ($entity instanceof GetChangesFeatureInterface) {
            $changes = $entity->getChanges();
            if (! $changes) {
                return 0; // zero affected
            }
            // remove not changed values from update
            $tmp = [];
            foreach ($changes as $fieldName => $v) {
                $changedField = $this->definition->getField($fieldName)->getMapping();
                $tmp[$changedField] = $entityValues[$changedField];
            }
            $entityValues = $tmp;
        }
        // mprd($entityValues['is_active']);

        $where = new Where\Equal(
            $this->definition->getPrimary(),
            EntityTools::getValue(
                $this->definition->getPrimary(),
                $entity,
                $this->definition
            )
        );
        $where->setDefaultDefinition($this); // XXX to można jakoś zautomatyzować?

        $statment = $this->adapter->createStatement();
        $update = (new ZfSql\Update($this->definition->getTable()))
            ->set($entityValues)
            ->where(array($where))
        ;
        $update->prepareStatement($this->adapter, $statment);


        $event = new Event\EntityOperation($this, $entity);
        $this->eventManager->trigger('pre.update', $event);
        try {
            $affected = $statment->execute()->getAffectedRows();
        } catch (DbException $e) {
            $this->eventManager->trigger('fail.update', $event->setParam('exception', $e));

            // catch unique violation exceptions
            $previous = $e->getPrevious();
            if ($previous instanceof PDOException && $previous->getCode() == 23505) {
                throw new Exception\DuplicateKeyException($previous->getMessage(), $previous->getCode(), $previous);
            }

            throw new Exception\StatementException($update->getSqlString(), 0, $e);
        }

        // fuckup
        if (1 !== $affected) {
            if (0 === $affected) {
                throw new Exception\NotAffectedException(
                    'update failure - did not changed any rows'
                );
            } else {
                throw new Exception\AmbiguousException(
                    ExceptionTools::msg('update failure - to many affected rows (%s)', $affected)
                );
            }
        }

        $this->eventManager->trigger('post.update', $event); // after exec markAsOrigin() changes will be not available

        if ($entity instanceof OriginValuesFeatureInterface) {
            $entity->markAsOrigin();
        }


        return $affected;
    }

    public function delete($entity)
    {
        $this->definition->assertEntityClass($entity);

        $where = new Where\Equal(
            $this->definition->getPrimary(),
            EntityTools::getValue(
                $this->definition->getPrimary(),
                $entity,
                $this->definition
            )
        );
        $where->setDefaultDefinition($this);

        $statment = $this->adapter->createStatement();
        $delete = (new ZfSql\Delete($this->definition->getTable()))
            ->where(array($where))
        ;
        $delete->prepareStatement($this->adapter, $statment);

        $event = new Event\EntityOperation($this, $entity);
        $this->eventManager->trigger('pre.delete', $event);

        try {
            $affected = $statment->execute()->getAffectedRows();
        } catch (DbException $e) {
            $this->eventManager->trigger('fail.delete', $event->setParam('exception', $e));
            throw new Exception\StatementException($update->getSqlString(), 0, $e);
        }

        // fuckup
        if (1 !== $affected) {
            if (0 === $affected) {
                throw new Exception\NotAffectedException(
                    'delete failure - did not changed any rows'
                );
            } else {
                throw new Exception\AmbiguousException(
                    ExceptionTools::msg('delete failure - to many affected rows (%s)', $affected)
                );
            }
        }

        $this->eventManager->trigger('post.delete', $event);

        return $affected;
    }

    /**
     * @return integer affected rows
     */
    public function deleteAll()
    {
        $this->eventManager->dispatchEvent(Events::preDeleteAll, $eventArgs);
        $aff = $this->adapter->delete($this->getTableName());
        $this->eventManager->dispatchEvent(Events::postDeleteAll, $eventArgs);
        return $aff;
    }

    public function count(Where $where = null)
    {

        return $this->_count($where);
    }

    /**
     * @see RepositoryTest::testGet()
     */
    public function get($where, $val = Expr::UNDEFINED)
    {
        return $this->getByMethod(
            'selectCacheable',
            $this->getWhereFn($where, $val)
        );
    }

    /**
     * @see RepositoryTest::testFetch()
     */
    public function fetch($where = null, $val = Expr::UNDEFINED)
    {
        if (null === $where) { // fetch all
            $where = function($select) {};
        }
        return $this->fetchByMethod(
            'selectCacheable',
            $this->getWhereFn($where, $val)
        );
    }

    public function getByMethod($method/*[$arg1, $argN]*/)
    {
        if (! method_exists($this, $method)) {
            throw new Exception\BadMethodCallException(
                ExceptionTools::msg('method %s::%s() does not exist', get_class($this), $method)
            );
        }

        $args = func_get_args();
        array_shift($args);

        // here is query to storage
        $data = call_user_func_array(array($this, $method), $args);

        $cacheId = false;
        $exceptionQuery = '';
        if ($data instanceof CacheableResult) {
            $cacheId = $data->getCacheId();
            // debuge($cacheId);
            if ($this->cache->getstorage()->hasItem($cacheId)) {
                // debuge($this->cache->getstorage()->getItem($cacheId), $cacheId);
                return unserialize($this->cache->getstorage()->getItem($cacheId));
            }
            $exceptionQuery = sprintf(' [%s]', $data->getExceptionQuery());
            $data = $data();
        }

        if (is_array($data)) {
            if (1 !== count($data)) {
                // not found
                if (empty($data)) {
                    // debuge($q);
                    throw new Exception\NotFoundException(
                        ExceptionTools::msg('record not found in repository %s(%s) %s', get_class($this), $this->getDsn(), $exceptionQuery)
                    );
                // ambigous
                } else {
                    throw new Exception\AmbiguousException(
                        ExceptionTools::msg('ambiguous in repository %s(%s) %s', get_class($this), $this->getDsn(), $exceptionQuery)
                    );
                }
            }
            $data = $this->createEntity($data[0]); // here is the certainty, that the entity class is correct
        } else {
            $entityClass = $this->getDefinition()->getOptions()->getEntityClass();
            if (! $data instanceof $entityClass) {
                throw new Exception\InvalidClassObjectException(
                    ExceptionTools::msg(
                        'method %s::%s() must return entity object class %s or row data array, given %s',
                        get_class($this), $method, $entityClass, $data
                    )
                );
            }
        }
        if ($cacheId) {
            $this->cache->getStorage()->setItem($cacheId, serialize($data));
        }
        return $data;
    }

    public function fetchByMethod($method/*[,$arg1, $argN]*/)
    {
        if (! method_exists($this, $method)) {
            throw new Exception\BadMethodCallException(
                ExceptionTools::msg('method %s::%s() does not exist', get_class($this), $method)
            );
        }

        $args = func_get_args();
        array_shift($args);

        // call work method
        $data = call_user_func_array(array($this, $method), $args);

        $cacheId = false;
        $exceptionQuery = '';
        if ($data instanceof CacheableResult) {
            $cacheId = $data->getCacheId();
            if ($this->cache->getstorage()->hasItem($cacheId)) {
                return $this->cache->getstorage()->getItem($cacheId);
            }
            $exceptionQuery = sprintf(' [%s]', $data->getExceptionQuery());
            $data = $data(); // here is query to storage (maybe via adapter)
        }

        if (is_array($data)) {
            $data = $this->createCollection($data);
        } else {
            $collectionClass = $this->getDefinition()->getOptions()->getEntityClass();
            if (! $data instanceof $collectionClass) {
                throw new Exception\InvalidObjectException(
                    ExceptionTools::msg(
                        'method %s::%s() must return collection object class %s or row data array, given %s',
                        get_class($this), $method, $collectionClass, $data
                    )
                );
            }
        }
        // mprd($data);
        if ($cacheId) {
            $this->cache->getstorage()->setItem($cacheId, $data);
        }
        return $data;
    }

    /**
     * @deprecated use factoryEntity()
     */
    public function createEntity(array $data = array())
    {
        return $this->factoryEntity($data);
    }

    public function factoryEntity(array $data = array())
    {
        $entityClass = $this->getDefinition()->getOptions()->getEntityClass();
        $entity = new $entityClass;
        $this->applyAwareElements($entity);

        if (! empty($data)) {
            // TODO allow the use of * in select stataments
            // this will require additional data mapping (this will be slower)
            // $this->getDefinition()->getHydrator()->hydrate($data, $entity);
            EntityTools::populate($data, $entity, $this->getDefinition());
        }

        if ($entity instanceof OriginValuesFeatureInterface) {
            $entity->markAsOrigin($data ?: null);
        }
        return $entity;
    }

    /**
     * @deprecated use factoryCollection()
     */
    public function createCollection(array $rows = array())
    {
        return $this->factoryCollection($rows);
    }

    public function factoryCollection(array $rows = array())
    {
        $collectionClass = $this->definition->getOptions()->getCollectionClass();
        $collection = new $collectionClass;
        $this->applyAwareElements($collection);

        foreach ($rows as $row) {
            $collection[] = $this->createEntity($row);
        }
        return $collection;
    }

    public function existsSimilar($entity)
    {
        return 1 > $this->fetchSimilar($entity)->count();
    }

    public function fetchSimilar($entity)
    {
        $this->definition->assertEntityClass($entity);
        $conditions = $entity->toArray();
        unset($conditions[$this->definition->getPrimary()->name]);
        return $this->fetch($conditions);
    }

    public function exists($entity)
    {
        $this->definition->assertEntityClass($entity);
        return null !== EntityTools::getValue(
            $this->definition->getPrimary(),
            $entity,
            $this->definition
        );
    }

    public function save($entity)
    {
        $this->definition->assertEntityClass($entity);

        //$this->eventManager->dispatchEvent(Events::preSave, $eventArgs);

        if ($this->exists($entity)) {
            $this->update($entity);
        } else {
            $this->insert($entity);
        }

        //$this->eventManager->dispatchEvent(Events::postSave, $eventArgs);

        return $this;
    }

    public function getSelect(Closure $selectFn = null)
    {
        $def = $this->getDefinition();

        $select = (new Sql\Select)
            ->setDefinition($this->getDefinition())
            ->configureFromDefinition()
            // ->from($def->getTable())
            // ->columns($def->getMapping())

            // ->columns($this->useFieldsMapInSelect ? Definition::getFor($this->getEntityClass())->getMapping() : '*')
            // ->order($order ?: $this->getDefaultOrder())
            // ->where($where ? array($where) : array())
        ;
        if ($selectFn) {
            $selectFn($select);
        }
        if ($def->hasConstantValuesFields()) {
            foreach ($def->getConstantValuesFields() as $f) {
                $select->where(new Sql\Where\Equal($f->getName(), $f->getConstantValue()));
            }
        }

        return $select;
    }

    // public function getCacheId(/*[arg1, argN]*/)
    // {
    //     $args = func_get_args();
    //     array_unshift($args, $this->getDsn(), get_class($this));
    //     return join($args, $this->cache->getstorage()->getOptions()->getNamespaceSeparator());
    // }

    // Default method for getBy(), fetchBy()
    public function select(Closure $selectFn = null)
    {
        $statment = $this->adapter->createStatement();
        $select = $this->getSelect($selectFn);
        $select->prepareStatement($this->adapter, $statment);

        $event = new Event\Query($this, $select);

        try {
            $this->getEventManager()->trigger('pre.select', $event);

            $resource = $statment->execute()->getResource();
            $resource->setFetchMode(PDO::FETCH_ASSOC);
            $result = $resource->fetchAll();

            $this->getEventManager()->trigger('post.select', $event->setParam('result', $result));

            return $result;

        } catch (DbException $e) {
            $this->getEventManager()->trigger('fail.select', $event->setParam('exception', $e));
            // debuge($select->getSqlString());
            //$this->rollbackTransaction();
            throw new Exception\StatementException($select->getSqlString(), 0, $e);
        }
    }

    protected function selectCacheable(Closure $selectFn = null)
    {
        $statment = $this->adapter->createStatement();
        $select = $this->getSelect($selectFn);
        $select->prepareStatement($this->getAdapter(), $statment);

        return new CacheableResult($this, $select, function($event) use ($statment, $select) {
            try {
                // mpr($select->getSqlString());
                $resource = $statment->execute()->getResource();
                $resource->setFetchMode(PDO::FETCH_ASSOC);
                return $resource->fetchAll();

            } catch (DbException $e) {
                // mprd($select->getSqlString());
                //$this->rollbackTransaction();
                $this->getEventManager()->trigger('fail.select', $event->setParam('exception', $e));
                throw new Exception\StatementException($select->getSqlString($this->getAdapter()->getPlatform()), 0, $e);
            }
        });
    }

    protected function getWhereFn($where, $val = Expr::UNDEFINED)
    {
        $equal2Where = function ($fieldName, $val) {
            if ($val instanceof Where) {
                return $val;
            } elseif (is_bool($val)) {
                return new Where\Bool($fieldName, $val);
            } elseif (null === $val) {
                return new Where\Nul($fieldName);
            } else {
                return new Where\Equal($fieldName, $val);
            }
        };
        switch (true) {
            case $where instanceof Closure:
                return $where;
            case $where instanceof Where:
                break;
            case is_string($where) && $val === Expr::UNDEFINED:
                $where = (new Where($where));
                break;
            case is_string($where):
                $where = $equal2Where($where, $val);
                break;
            case is_array($where) && $where:
                $_where = $equal2Where(key($where), current($where));
                array_shift($where);
                while(!empty($where)) {
                    $_where->andRule($equal2Where(key($where), current($where)));
                    array_shift($where);
                }
                $where = $_where;
                break;
            default:
                throw new Exception\InvalidArgumentException(
                    ExceptionTools::msg('argument where must be Rule\Where|Closure|array|string, given %s', $where)
                );
        }
        // dbgd($where);
        return function(Sql\Select $select) use ($where) {
            $where->setDefaultDefinition($this);
            $select->where(array($where));
        };
    }

    protected function applyAwareElements($object)
    {
        if ($object instanceof RepositoryAwareInterface) {
            $object->setRepository($this);
        }
        if ($object instanceof DefinitionAwareInterface) {
            $object->setDefinition($this->getDefinition());
        }


        if ($object instanceof ServiceManagerAwareInterface) {
            try {
                $object->setServiceManager($this->getOptions()->getServiceManager());

            } catch (Exception\RuntimeException $e) {
                // service manager maybe not set, that's no error
            }
        }
        return $object;
    }
}
