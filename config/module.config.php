<?php
return [
    'BrsZfSloth' => [
        'options' => [
            'default_db_adapter' => 'Zend\Db\Adapter\Adapter',
            'definitions_paths' => [],
            // ignored db tables during generate definitions
            'definition_generator_ignored_db_tables' => [],
        ]
    ],
    'console' => [
        'router' => [
            'routes' => [
                // 'user-reset-password' => [
                //     'options' => [
                //         'route'    => 'user passwd [--sendMail] <email>',
                //         'constraints' => [
                //             'email' => '/^[^@]*@[^@]*\.[^@]*$/'
                //         ],
                //         'defaults' => [
                //             'controller' => 'User\Controller\Index',
                //             'action'     => 'time',
                //         ],
                //     ],
                // ],
                'BrsZfSloth.Definition.Init' => [
                    'options' => [
                        'route'    => 'sloth def init [--skip-existing|-s]',
                        'defaults' => [
                            'controller' => 'BrsZfSloth.Controller.Generator',
                            'action'     => 'initdb',
                        ],
                    ],
                ],
                'BrsZfSloth.Definition.Generate' => [
                    'options' => [
                        'route'    => 'sloth def generate <table>',
                        'constraints' => [
                            'table' => '/^([a-z0-9_]*\.)?[a-z0-9_]{1,}$/'
                        ],
                        'defaults' => [
                            'controller' => 'BrsZfSloth.Controller.Generator',
                            'action'     => 'generateTableDefinition',
                        ],
                    ],
                ],
                'BrsZfSloth.Definition.FlushCache' => [
                    'options' => [
                        'route'    => 'sloth def clearcache <definition>',
                        'constraints' => [
                            'definition' => '/^([a-z0-9_]*\.)?[a-z0-9_]{1,}$/'
                        ],
                        'defaults' => [
                            'controller' => 'BrsZfSloth.Controller.Cache',
                            'action'     => 'definitionClearCache',
                        ],
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'invokables' => [
            'BrsZfSloth.Controller.Generator' => 'BrsZfSloth\Controller\GeneratorController',
            'BrsZfSloth.Controller.Cache' => 'BrsZfSloth\Controller\CacheController',
        ],
    ],
    // 'view_manager' => [
    //     'template_path_stack' => [
    //         'zfcuser' => __DIR__ . '/../view',
    //     ],
    // ],
    // 'controllers' => [
    //     'invokables' => [
    //         'zfcuser' => 'ZfcUser\Controller\UserController',
    //     ],
    // ],
    'controller_plugins' => [
        'invokables' => [
            'DefaultDbConnection' => 'BrsZfSloth\Controller\Plugin\DefaultDbConnection',
        ],
    ],
    // 'service_manager' => [
    //     'aliases' => [
    //         'zfcuser_zend_db_adapter' => 'Zend\Db\Adapter\Adapter',
    //     ],
    // )
];