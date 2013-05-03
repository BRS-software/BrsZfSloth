<?php
namespace BrsZfSloth\Repository;

use Closure;

use Zend\Db\Sql\Select;
use Zend\EventManager\EventManager;
use Zend\Stdlib\ErrorHandler;

use BrsZfSloth\Event;

class CacheableResult implements CacheableResultInterface
{
    protected $repository;
    protected $select;
    protected $execFn;
    protected $sqlString; // cache

    public function __construct(Repository $repository, Select $select, Closure $execFn)
    {
        $this->repository = $repository;
        $this->select = $select;
        $this->execFn = $execFn;
    }

    public function getCacheId()
    {
        return 'select'.sha1($this->getSqlString());
    }

    public function getExceptionQuery()
    {
        return $this->getSqlString();
    }

    public function __invoke()
    {
        $event = new Event\Query($this->repository, $this->select);
        $this->repository->getEventManager()->trigger('pre.select', $event);

        $fn = $this->execFn;
        $result = $fn($event);

        $event->setParam('result', $result);
        $this->repository->getEventManager()->trigger('post.select', $event);

        return $result;
    }

    private function getSqlString()
    {
        if (null === $this->sqlString) {
            // catch error
            // Attempting to quote a value without specific driver level support can introduce security vulnerabilities in a production environment.
            ErrorHandler::start(\E_ALL);
            $this->sqlString = $this->select->getSqlString();
            ErrorHandler::stop();
        }
        // dbg(ErrorHandler::getNestedLevel());
        return $this->sqlString;
    }
}