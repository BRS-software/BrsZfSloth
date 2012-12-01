<?php
namespace BrsZfSloth\Event;

use Zend\EventManager\Event;
use Zend\Db\Sql\Select;

use BrsZfSloth\Repository\Repository;

class Query extends Event
{
    public function __construct(Repository $target, Select $select)
    {
        // parent::__construct($when.'.retrieveData', $target, [
        //     'select' => $select,
        //     'result' => $result,
        // ]);
        $this->setTarget($target);
        $this->setParam('select', $select);
    }
}