<?php
namespace BrsZfSloth\Repository;

use Closure;

use Zend\Db\Sql\Select;
use Zend\EventManager\EventManager;

use BrsZfSloth\Event;

class CacheableResult implements CacheableResultInterface
{
    protected $repository;
    protected $select;
    protected $execFn;

    public function __construct(Repository $repository, Select $select, Closure $execFn)
    {
        $this->repository = $repository;
        $this->select = $select;
        $this->execFn = $execFn;
    }

    public function getCacheId()
    {
        return 'select'.sha1($this->select->getSqlString());
    }

    public function getExceptionQuery()
    {
        return $this->select->getSqlString();
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
}