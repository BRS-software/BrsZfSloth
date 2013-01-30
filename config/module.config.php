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
                        'route'    => 'sloth definition init [--skip-existing|-s]',
                        'defaults' => [
                            'controller' => 'BrsZfSloth.Controller.Generator',
                            'action'     => 'initdb',
                        ],
                    ],
                ],
                'BrsZfSloth.Definition.Generate' => [
                    'options' => [
                        'route'    => 'sloth definition generate <table>',
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
                        'route'    => 'sloth definition clearcache <definition>',
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
    // 'controller_plugins' => [
    //     'invokables' => [
    //         'zfcuserauthentication' => 'ZfcUser\Controller\Plugin\ZfcUserAuthentication',
    //     ],
    // ],
    // 'service_manager' => [
    //     'aliases' => [
    //         'zfcuser_zend_db_adapter' => 'Zend\Db\Adapter\Adapter',
    //     ],
    // )
];