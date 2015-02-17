<?php
namespace BrsZfSloth;

use RuntimeException;

use Zend\ModuleManager\ModuleManager;
use Zend\Mvc\ApplicationInterface;
use Zend\Loader\AutoloaderFactory;
use Zend\ModuleManager\Feature\ConsoleBannerProviderInterface;
use Zend\Console\Adapter\AdapterInterface as Console;
use Zend\Mvc\MvcEvent;
use Zend\EventManager\EventInterface as Event;

use BrsZfBase\Module\AbstractModule;
use BrsZfSloth\Sloth;
use BrsZfSloth\Options as SlothOptions;

class Module extends AbstractModule implements ConsoleBannerProviderInterface
{
    public function getConsoleBanner(Console $console)
    {
        return <<<EOF
==--------==
BRS ZF SLOTH
==--------==
usage:
sloth def init [--skip-existing]         init all db tables
sloth def generate <table>               generate definition for table
sloth def clearcache <definitionName>    clear definition cache
sloth def flushcache                     flush all definitions cache
EOF;
    }

    public function getDir()
    {
        return __DIR__;
    }

    public function getNamespace()
    {
        return __NAMESPACE__;
    }

    public function modulesLoaded(Event $e)
    {
        $slothOptions = $this->getOptions();
        $slothOptions['default_db_adapter'] = $this->serviceManager->get($this->getOption('default_db_adapter'));
        $slothOptions['default_service_manager'] = $this->serviceManager;

        Sloth::configure(
            (new SlothOptions($slothOptions))
        );
    }
}
