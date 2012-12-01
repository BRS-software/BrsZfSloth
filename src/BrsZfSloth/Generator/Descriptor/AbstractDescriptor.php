<?php
namespace BrsZfSloth\Generator\Descriptor;

use Zend\Db\Adapter\Adapter as DbAdapter;

// use BrsZfSloth;
use BrsZfSloth\Exception;
use BrsZfSloth\Exception\ExceptionTools;
// use BrsZfSloth\Repository;

abstract class AbstractDescriptor
{
    protected $adapter;
    protected $dbName;

    abstract public function describeDatabase($schema = Definition::DEFAULT_SCHEMA);
    abstract public function describeTable($tableName, $schema = Definition::DEFAULT_SCHEMA);

    public static function factory(DbAdapter $adapter)
    {
        preg_match('/^([^\:]+)/', $adapter->getDriver()->getConnection()->getConnectionParameters()['dsn'], $m);
        $driver = ucfirst($m[1]);
        $descriptorClass = __NAMESPACE__ . '\\' . $driver;

        if (! class_exists($descriptorClass)) {
            throw new Exception\UnsupportedException(
                ExceptionTools::msg('descriptor for driver %s is unavailable', $driver)
            );
        }
        return new $descriptorClass($adapter);
    }

    public function __construct(DbAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function getDsn()
    {
        return $this->adapter->getDriver()->getConnection()->getConnectionParameters()['dsn'];
    }

    public function getDbName()
    {
        if (null === $this->dbName) {
            preg_match('/dbname=([^\;]+)/', $this->getDsn(), $m);
            $this->dbName = $m[1];
        }
        return $this->dbName;
    }
}