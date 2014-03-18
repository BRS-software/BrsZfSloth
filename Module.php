<?php
namespace BrsZfSloth;

use RuntimeException;

use Zend\ModuleManager\ModuleManager;
use Zend\Mvc\ApplicationInterface;
use Zend\Loader\AutoloaderFactory;
use Zend\ModuleManager\Feature\ConsoleBannerProviderInterface;
use Zend\Console\Adapter\AdapterInterface as Console;

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

    public function bootstrap(ModuleManager $moduleManager, ApplicationInterface $app)
    {
        $sm = $app->getServiceManager();
        $slothOptions = $this->getOptions();
        $slothOptions['default_db_adapter'] = $sm->get($this->getOption('default_db_adapter'));
        $slothOptions['default_service_manager'] = $sm;
        // dbgd($slothOptions['definition_generator_ignored_db_tables']);

        Sloth::configure(
            (new SlothOptions($slothOptions))
                // ->setDefinitionCaching(true) // XXX is_production
        );
    }

    // public function bootstrap(ModuleManager $moduleManager, ApplicationInterface $app)
    // {
    //     debuge(new Sloth);

    //     $sm      = $app->getServiceManager();
    //     $options = $sm->get('BrsZfSloth_module_options');

    //     // debuge($this->getMergedConfig());
    //     // debuge($this->getOption('sloth_lib_path'));
    //     // debuge($options->sloth_lib_path);


    //     // Sloth lib autoloader
    //     AutoloaderFactory::factory(array(
    //         'Zend\Loader\ClassMapAutoloader' => array(
    //             $options->sloth_lib_path . '/autoload_classmap.php',
    //         ),
    //         'Zend\Loader\StandardAutoloader' => array(
    //             'namespaces' => array(
    //                 'Sloth' => $options->sloth_lib_path . '/src/Sloth'
    //             ),
    //         ),
    //     ));

    //     // Sloth lib configuration
    //     Sloth::configure($options->getSlothOptions()
    //         ->setCaching(is_production())
    //         ->setEventManager($app->getEventManager())
    //         ->setDbAdapter($sm->get('Zend\Db\Adapter\Adapter'))
    //     );
    // }

    public function getServiceConfig()
    {
        return array(
            // 'factories' => array(
            //     'BrsZfSloth_options' => function ($sm) {
            //         //$config = $sm->get('Config');
            //         // dbgd($this->getOptions());
            //         return new Options\ModuleOptions($this->getOptions());
            //     },
            //     // 'db-adapter' => function($sm) {
            //     //     $config = $sm->get('config');
            //     //     $config = $config['db'];
            //     //     $dbAdapter = new DbAdapter($config);
            //     //     return $dbAdapter;
            //     // },
            // ),
        );
    }
}
