<?php
namespace BrsZfSloth\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Console\Prompt\Line;
use Zend\Console\Prompt\Confirm;
use Zend\Console\Prompt\Select;

use BrsZfSloth\Sloth;
use BrsZfSloth\Exception;
// use BrsZfSloth\Definition\Definition;
// use BrsZfSloth\Generator\DefinitionGenerator;

class CacheController extends AbstractActionController
{
    protected $skipExisting = false;

    public function definitionClearCacheAction()
    {
        print 'xxx';
        $cache = Sloth::getOptions()->getDefinitionCache();
        dbgod($cache->getItem('public.user'));
        print 'clean cache';
    }
}