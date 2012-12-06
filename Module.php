<?php
namespace BrsZfSloth;

require_once __DIR__ . '/src/Brs/Zend/Module/AbstractModule.php';

use Brs\Zend\Module\AbstractModule;
use Zend\ModuleManager\ModuleManager;
use Zend\Mvc\ApplicationInterface;
use Zend\Loader\AutoloaderFactory;

use RuntimeException;
use BrsZfSloth\Sloth;
use BrsZfSloth\Options as SlothOptions;

class Module extends AbstractModule
{
    public function getDir()
    {
        return __DIR__;
    }
    public function getNamespace()
    {
        return 'Brs\Zend';
    }

    public function bootstrap(ModuleManager $moduleManager, ApplicationInterface $app)
    {
        $sm = $app->getServiceManager();
        // $options = $sm->get('BrsZfSloth_module_options');

        // debuge($this->getOption('Sloth'));
        debuge($sm->setDbAdapter($sm->get('Zend\Db\Adapter\Adapter')));
        Sloth::configure(
            (new SlothOptions)
                // ->setCaching(is_production())
                ->setEventManager($app->getEventManager())
                ->setDbAdapter($sm->get($this->getOption('Sloth.defaultDbAdapter')))
        );
        // debuge($q=$sm->get('Zend\Db\Adapter\Adapter')->query('select * from user'));
        // debuge($sm->get('Zend\Db\Adapter\Adapter')->getDriver()->createResult($q));
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
        // debuge($this->getMergedConfig());
        return array();

        $that = $this;
        return array(
            'factories' => array(
                'BrsZfSloth_module_options' => function ($sm) use ($that) {
                    //$config = $sm->get('Config');
                    return new Options\ModuleOptions($that->getOptions());
                },
                // 'db-adapter' => function($sm) {
                //     $config = $sm->get('config');
                //     $config = $config['db'];
                //     $dbAdapter = new DbAdapter($config);
                //     return $dbAdapter;
                // },
            ),
        );
    }
}
