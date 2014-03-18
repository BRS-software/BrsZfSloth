<?php
namespace BrsZfSloth\Definition;

use __;
use Countable;
use SeekableIterator;
use ArrayAccess;
use ReflectionClass;
use DirectoryIterator;
use InvalidArgumentException;

use Zend\Stdlib\AbstractOptions;
use Zend\Stdlib\Hydrator\HydratorInterface;
use Zend\Cache\Storage\FlushableInterface as CacheFlushable;
use Zend\Cache\Storage\ClearByNamespaceInterface as CacheClearByNamespace;
use Zend\Db\Sql\TableIdentifier;

use BrsZfSloth\Exception;
use BrsZfSloth\Exception\ExceptionTools;
use BrsZfSloth\Sloth;
use BrsZfSloth\Sql\Order;
use BrsZfSloth\Hydrator\Hydrator;

class Definition implements
    Countable,
    SeekableIterator,
    ArrayAccess
    // DefinitionAwareInterface
{
    const ENTITY = 'ENTITY';
    const REPOSITORY = 'REPOSITORY';
    const DEFAULT_SCHEMA = 'public';

    protected $options;

    protected $name; // name of definition
    protected $schema = self::DEFAULT_SCHEMA;
    protected $table;
    protected $hydrator;
    protected $defaultOrder;
    protected $primary;
    protected $originFile;
    protected $fields = [];
    protected $mapping = [];
    protected $constantValuesFields;
    protected $uniqueKeys = [];


    public static function reset()
    {
        self::flushCache();
    }

    public static function flushCache()
    {
        $cache = Sloth::getOptions()->getDefinitionCache();
        if ($cache instanceof CacheClearByNamespace) {
            $cache->clearByNamespace($cache->getOptions()->getNamespace());
        } elseif ($cache instanceof CacheFlushable) {
            $cache->flush();
        } else {
            throw new Exception\RuntimeException(
                'cache adapter could not be flushed'
            );
        }
    }

    public static function clearCache($definitionName)
    {
        Sloth::getOptions()->getDefinitionCache()->removeItem($definitionName);
    }

    public static function factory($definition)
    {
        if ($definition instanceof DefinitionProviderInterface) {
            return new self($definitionName->getDefinitionConfig());
        } elseif (is_array($definition)) {
            return new self($definition);
        } else {
            throw new Exception\InvalidArgumentException(
                ExceptionTools::msg('paramter must be DefinitionProviderInterface or array, given %s', $definition)
            );
        }
    }

    public static function getCachedInstance($definitionName)
    {
        // if ($definitionName instanceof DefinitionAwareInterface) {
            // $definitionName = $definitionName->getDefinitionName();
        if ($definitionName instanceof DefinitionProviderInterface) {
            $defConfig = $definitionName->getDefinitionConfig();
        } elseif (is_array($definitionName)) {
            $defConfig = $definitionName;
        } elseif (! is_string($definitionName)) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    'argument must be definition name string or config array or DefinitionAwareInterface or DefinitionProviderInterface, given "%s"',
                    is_object($definitionName) ? get_class($definitionName) : gettype($definitionName)
                )
            );
        }

        if (isset($defConfig['name'])) {
            $definitionName = $defConfig['name'];
        } elseif (isset($defConfig)) {
            throw new Exception\IncorrectDefinitionException('Name of definition must be set');
        }

        $cache = Sloth::getOptions()->getDefinitionCache();
        // dbgd($cache->getCaching());

        if ($cache->hasItem($definitionName)) {
            return $cache->getItem($definitionName);
        }

        if (! isset($defConfig)) {
            $defConfig = self::discoverConfig($definitionName);
        }
        $definition = new self($defConfig);
        $cache->setItem($definitionName, $definition);
        return $definition;
    }

    public static function discoverConfig($name, $path = null)
    {
        if (null === $path) {
            $paths = Sloth::getOptions()->getDefinitionsPaths(true);
        } elseif (is_string($path)) {
            $paths = [$path];
        } else {
            throw new Exception\InvalidArgumentException(
                ExceptionTools::msg('path must be string, given %s', $path)
            );
        }
        foreach ($paths as $path) {
            foreach (new DirectoryIterator($path) as $fileinfo) {
                $basename = $fileinfo->getBaseName('.' . $fileinfo->getExtension());
                if ($fileinfo->isFile() && $basename === $name) {
                    switch (strtolower($fileinfo->getExtension())) {
                        case 'json':
                            $config = json_decode(file_get_contents($fileinfo->getPathname()), true);
                            if (! is_array($config)) {
                                throw new Exception\IncorrectDefinitionException(
                                    sprintf('file %s has no informations about definition or json format is invalid', $fileinfo->getPathname())
                                );
                            }
                            break;
                        default:
                            throw new Exception\UnsupportedException(
                                sprintf('unsupported definition config format for file "%s"', $fileinfo->getPathname())
                            );
                    }
                    $config['origin_file'] = $fileinfo->getPathname();
                    return $config;
                }
            }
        }

        throw new Exception\DefinitionConfigNotFoundException(
            sprintf('definition config not found for name "%s" (paths: %s)', $name, join(':', $paths))
        );
    }

    // public static function mergeDefinitions()
    // {
    //     $merged = array();
    //     foreach (func_get_args() as $def) {
    //         Assert::objectClass($def, get_class());
    //         foreach($def as $f) {
    //             $merged[$f->name] = $f->toArray();
    //         }
    //     }
    //     return $merged;
    // }

    public function __construct($definitionConfig, $definitionOptions = null)
    {
        // arg0
        if ($definitionConfig instanceof DefinitionProviderInterface) {
            $definitionConfig = $definitionConfig->getDefinitionConfig();
        } elseif (! is_array($definitionConfig)) {
            throw new Exception\InvalidArgumentException(
                sprintf('argument must be config array or DefinitionProviderInterface, given "%s"', gettype($definitionOptions))
            );
        }

        // arg1
        if (null === $definitionOptions) {
            $this->options = new DefinitionOptions;
        } elseif ($definitionOptions instanceof DefinitinOptions) {
            $this->options = clone $definitionOptions;
        } elseif (is_array($definitionOptions)) {
            $this->options = new DefinitinOptions($definitionOptions);
        } else {
            throw new Exception\InvalidArgumentException(
                sprintf('$definitionOptions argument must be config array or instance of DefinitinOptions, given "%s"', gettype($definitionOptions))
            );
        }
        // construct
        if (! isset($definitionConfig['name'])) {
            throw new Exception\IncorrectDefinitionException(
                'parametr "name" is required in definition config'
            );
        }
        $this->name = $definitionConfig['name'];
        unset($definitionConfig['name']);

        if (! isset($definitionConfig['table'])) {
            throw new Exception\IncorrectDefinitionException(
                'parametr "table" is required in definition config'
            );
        }
        $this->table = $definitionConfig['table'];
        unset($definitionConfig['table']);

        if (isset($definitionConfig['schema'])) {
            $this->schema = $definitionConfig['schema'];
        }
        unset($definitionConfig['schema']);

        if (isset($definitionConfig['origin_file'])) {
            $this->originFile = $definitionConfig['origin_file'];
        }
        unset($definitionConfig['origin_file']);

        if (isset($definitionConfig['fields'])) {
            $fields = $definitionConfig['fields'];
        } else {
            $fields = [];
        }
        unset($definitionConfig['fields']);

        // forward cofig to options (config replace options previously set)
        $this->getOptions()->setFromArray($definitionConfig);

        // init hydrator
        $this->setHydrator($this->getOptions()->getHydrator());

        // init default order
        $this->setDefaultOrder(new Order($this->getOptions()->getDefaultOrder()));

        // add fields
        foreach ($fields as $k => $v) {
            if (! $v instanceof Field) {
                $v = new Field($k, $v);
            }
            $this->addField($v);
        }
    }

    public function __toString()
    {
        return $this->getName();
        // return sprintf('%s: %s.%s', $this->getName(), $this->getSchema(), $this->getTable());

    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getName()
    {
        return $this->name;
    }

    public function comesFromFile()
    {
        return null !== $this->originFile;
    }

    public function getOriginFile()
    {
        if (null === $this->originFile) {
            throw new Exception\RuntimeException(
                ExceptionTools::msg('definition %s does not come from a file', $this)
            );
        }
        return $this->originFile;
    }

    public function getSchema()
    {
        return $this->schema;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function getTableIdentifier()
    {
        return new TableIdentifier($this->getTable(), $this->getSchema());
    }

    public function setHydrator(HydratorInterface $hydrator)
    {
        if ($hydrator instanceof DefinitionAwareInterface) {
            $hydrator->setDefinition($this);
        }
        $this->addHydratorStrategies($hydrator);
        $this->hydrator = $hydrator;
        return $this;
    }

    public function getHydrator()
    {
        return $this->hydrator;
    }

    public function addHydratorStrategies(HydratorInterface $hydrator = null)
    {
        if (null === $hydrator) {
            $hydrator = $this->getHydrator();
        }
        foreach ($this->getFields() as $field) {
            $field->addHydratorStrategy($hydrator);
        }
    }

    public function removeHydratorStrategies(HydratorInterface $hydrator = null)
    {
        if (null === $hydrator) {
            $hydrator = $this->getHydrator();
        }
        foreach ($this->getFields() as $field) {
            $field->removeHydratorStrategy($hydrator);
        }
    }

    public function setDefaultOrder(Order $order)
    {
        $this->defaultOrder = $order;
        return $this;
    }

    public function getDefaultOrder()
    {
        return $this->defaultOrder;
    }

    // TODO UT
    public function getNotNullFields()
    {
        $notNull = [];
        foreach ($this as $field) {
            if ($field->isNotNull()) {
                $notNull[] = $field;
            }
        }
        return $notNull;
    }

    /**
     * Used to getting last inserted id
     * @todo always will be work correctly for other databases than pgsql?
     * @see $dbAdapter->getDriver()->getConnection()->getLastGeneratedValue('testtable_id_user_seq')
     */
    public function getLastGeneratedValueParam()
    {
        return sprintf('%s_%s_seq', $this->getTable(), $this->getPrimary()->getMapping());
    }

    public function assertEntityClass($entity)
    {
        $allowedClass = $this->getOptions()->getEntityClass();
        if (! is_object($entity)) {
            throw new Exception\InvalidArgumentException(
                'argument must be some entity object'
            );
        } elseif (! $entity instanceof $allowedClass) {
            throw new Exception\InvalidArgumentException(
                sprintf('entity must be instance of %s, given %s', $allowedClass, get_class($entity))
            );
        }
        return $this;
    }

    public function assertCollectionClass($collection)
    {
        $allowedClass = $this->getOptions()->getCollectionClass();
        if (! is_object($collection)) {
            throw new Exception\InvalidArgumentException(
                'argument must be some collection object'
            );
        } elseif (! $collection instanceof $allowedClass) {
            throw new Exception\InvalidArgumentException(
                sprintf('collection must be instance of %s, given %s', $allowedClass, get_class($collection))
            );
        }
        return $this;
    }

    // TODO UnitTest
    public function assertValue($fieldName, $value)
    {
        $field = $this->getField($fieldName);
        try {
            $field->assertValue($value);

        } catch (InvalidArgumentException $e) {
            throw new Exception\InvalidFieldValueException($this, $field, $e);
        }
        return $value;
    }

    // TODO UnitTest
    public function remapToEntity($data, $strict = false)
    {
        return $this->remap($data, self::ENTITY, $strict);
    }

    // TODO UnitTest
    public function remapToRepository($data, $strict = false)
    {
        return $this->remap($data, self::REPOSITORY, $strict);
    }

    protected function remap(array $data, $directTo, $strict = false)
    {
        switch ($directTo) {
            case self::REPOSITORY:
                $mapping = $this->getMapping();
                break;
            case self::ENTITY:
                $mapping = array_flip($this->getMapping());
                break;
            default:
                throw new Exception\InvalidArgumentException(
                    'direct argument must be one of the constants Definition::ENTITY or Definition::REPOSITORY'
                );
        }
        $result = [];
        foreach ($data as $k => $v) {
            if (isset($mapping[$k])) {
                $result[$mapping[$k]] = $v;
            } elseif ($strict) {
                throw new Exception\UnmappedException(
                    sprintf('not found mapping for %s in definition %s', $k, $this->getName())
                );
            }
        }
        return $result;
    }

    /**
     * Reject from values array where keys is not valid
     */
    protected function filterValues(array $data, $byFieldsFrom)
    {
        switch ($byFieldsFrom) {
            case self::REPOSITORY:
                $mapping = $this->getMapping();
                break;
            case self::ENTITY:
                $mapping = array_flip($this->getMapping());
                break;
            default:
                throw new Exception\InvalidArgumentException(
                    'byFieldsFrom argument must be one of the constants Definition::ENTITY or Definition::REPOSITORY'
                );
        }
        $result = [];
        foreach ($data as $k => $v) {
            if (in_array($k, $mapping)) {
                $result[$k] = $v;
            }
        }
        return $result;
    }

    // public function getEntityValues($entity)
    // {
    //     $this->assertEntityClass($entity);
    //     $values = [];

    //     if ($this->getOptions()->getHydrator() instanceof Hydrator) {
    //         mprd(1);

    //     } else {
    //         mprd(2);
    //     }

    //     mprd($this->getOptions()->getHydrator()->extract($entity));

    //     foreach ($this as $field) {
    //         $values[$field->getName()] = $field->getValue($entity);
    //     }
    // }



    public function addField(Field $field)
    {
        $fName = $field->getName();
        $this[$fName] = $field;
        $this->mapping[$fName] = $field->getMapping();
        $field->addHydratorStrategy($this->getHydrator());

        if ($field->primary) {
            $this->primary = $fName;
        }
        return $this;
    }

    public function hasField($name)
    {
        return array_key_exists($name, $this->fields);
    }

    public function getField($name)
    {
        $name = (string) $name; // cast to string Field object
        if (! array_key_exists($name, $this->fields)) {
            throw new Exception\OutOfBoundsException(
                sprintf('Field %s::%s not exists', $this->getName(), $name)
            );
        }
        return $this[$name];
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function resetFields()
    {
        $this->fields = array();
    }

    public function getPrimary()
    {
        if (null === $this->primary) {
            throw new Exception\NotFoundException(
                sprintf('Entity %s not include primary key', $this->entityClass)
            );
        }
        return $this->getField($this->primary);
    }

    // public function filterOutNonExistent(array $data)
    // {
    //     foreach ($data as $k => $v) {
    //         if (! isset($this[$k])) unset($data[$k]);
    //     }
    //     return $data;
    // }

    public function getMapping()
    {
        return $this->mapping;
    }

    public function getConstantValuesFields()
    {
        if ($this->constantValuesFields === null) {
            $this->constantValuesFields = [];
            foreach ($this as $f) {
                if ($f->hasConstantValue()) {
                    $this->constantValuesFields[] = $f;
                }
            }
        }
        return $this->constantValuesFields;
    }

    public function hasConstantValuesFields()
    {
        return ! empty($this->getConstantValuesFields());
    }

    public function count()
    {
        return count($this->fields);
    }

    // public function getExportFileName()
    // {
    //     if (self::DEFAULT_SCHEMA !== $schema) {
    //         $name = sprintf('%s.%s', $schema, $table);
    //     } else {
    //         $name = $table;
    //     }
    //     return DefinitionTools::transformUnderscoreToCamelCase($name);
    // }

    // TODO UnitTest
    public function export()
    {
        $export = [
            'name' => $this->getName(),
            'schema' => $this->getSchema(),
            'table' => $this->getTable(),
            'entityClass' => $this->getOptions()->getEntityClass(),
            'collectionClass' => $this->getOptions()->getCollectionClass(),
            'hydratorClass' => $this->getOptions()->getHydratorClass(),
        ];
        foreach ($this->getOptions()->getDefaultOrder() as $f => $sort) {
            $export['defaultOrder'][$f] = SORT_ASC === $sort ? 'asc' : 'desc';
        }
        foreach ($this as $f) {
            $export['fields'][] = $f->export();
        }
        return $export;
    }

    // public function getFieldBySetter($name)
    // {
    //     $fName = array_search(strtolower($name), array_map('strtolower', $this->setters));
    //     if (false === $fName) {
    //         throw new Exception\OutOfRangeException(
    //             sprintf('Setter %s::%s not exists', $this->getOptions()->getEntityClass(), $name)
    //         );
    //     }
    //     return $this->getField($fName);
    // }

    // public function getFieldByGetter($name)
    // {
    //     $fName = array_search(strtolower($name), array_map('strtolower', $this->getters));
    //     if (false === $fName) {
    //         throw new Exception\OutOfRangeException(
    //             sprintf('Getter %s::%s not exists', $this->getOptions()->getEntityClass(), $name)
    //         );
    //     }
    //     return $this->getField($fName);
    // }



    /**
     * @see    SeekableIterator::seek()
     * @param  integer $index
     */
    public function seek($index)
    {
        $this->rewind();
        $position = 0;

        while ($position < $index && $this->valid()) {
            $this->next();
            $position++;
        }
        if (!$this->valid()) {
            throw new Exception\OutOfBoundsException('Invalid seek position');
        }
    }

    /**
     * @see    SeekableIterator::current()
     * @return mixed
     */
    public function current()
    {
        return current($this->fields);
    }

    /**
     * @see    SeekableIterator::next()
     * @return mixed
     */
    public function next()
    {
        return next($this->fields);
    }

    /**
     * @see    SeekableIterator::key()
     * @return mixed
     */
    public function key()
    {
        return key($this->fields);
    }

    /**
     * @see    SeekableIterator::valid()
     * @return boolean
     */
    public function valid()
    {
        return ($this->current() !== false);
    }

    /**
     * @see    SeekableIterator::rewind()
     * @return void
     */
    public function rewind()
    {
        reset($this->fields);
    }

    /**
     * @see    ArrayAccess::offsetExists()
     * @param  mixed $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->fields);
    }

    /**
     * @see    ArrayAccess::offsetGet()
     * @param  mixed $offset
     */
    public function offsetGet($offset)
    {
        return $this->fields[$offset];
    }

    /**
     * @see    ArrayAccess::offsetSet()
     * @param  mixed $offset
     * @param  mixed $field
     */
    public function offsetSet($offset, $field)
    {
        if ($offset === null) {
            $this->fields[] = $field;
        } else {
            $this->fields[$offset] = $field;
        }
    }

    /**
     * @see    ArrayAccess::offsetUnset()
     * @param  mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->fields[$offset]);
    }
}