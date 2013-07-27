<?php

namespace BrsZfSlothTest\Repository;

use Zend\Db\Adapter\Adapter;

/**
 * @group integration-pgsql
 */
class PgsqlIntegrationTest extends AbstractIntegrationTest
{
    protected function setUp()
    {
        parent::setUp();

        if (!extension_loaded('pgsql')) {
            $this->fail('The phpunit group integration-pgsql was enabled, but the extension is not loaded.');
        }

        $this->adapter = new Adapter([
            'driver'         => 'Pdo',
            'dsn'            => sprintf('pgsql:dbname=%s;host=%s', $GLOBALS['SLOTH_INTEGRATION_DBNAME'], $GLOBALS['SLOTH_INTEGRATION_HOSTNAME']),
            'username'       => $GLOBALS['SLOTH_INTEGRATION_USERNAME'],
            'password'       => $GLOBALS['SLOTH_INTEGRATION_PASSWORD'],
            // 'driver_options' => array(
            //     PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''
            // ),
        ]);
        // mprd($this->adapter);
    }

    protected function setupTestTable()
    {
        $this->dropTestTable();
        $statement = $this->adapter->query('
            CREATE TABLE '.$this->testTableName.' (
                id_user serial NOT NULL,
                crt_date timestamp without time zone NOT NULL DEFAULT now(),
                short_name character varying(16) NOT NULL,
                is_active boolean NOT NULL DEFAULT false,
                comment text,
                CONSTRAINT users_pkey PRIMARY KEY (id_user),
                CONSTRAINT users_short_name_uniq UNIQUE (short_name)
            )
        ');
        $result = $statement->execute();
        // foreach($result as $data){
        //    var_dump($data);
        // }
    }

    protected function dropTestTable()
    {
        if ($this->adapter && false !== $this->adapter->query("select * from pg_tables where schemaname='public' and tablename='".$this->testTableName."'")->execute()->current()) {
            $this->adapter->query("drop table ".$this->testTableName)->execute();
        }
    }
}
