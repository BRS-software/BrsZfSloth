<?php

namespace BrsZfSloth\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use BrsZfSloth\Sloth;

class DefaultDbConnection extends AbstractPlugin
{
    public function __invoke()
    {
        return Sloth::getOptions()->getDefaultDbAdapter()->getDriver()->getConnection();
    }
}
