<?php
namespace BrsZfSlothTest\Repository;

use Zend\Db\Adapter\Adapter;

/**
 * @group integration-mysql
 */
class MysqlIntegrationTest extends AbstractIntegrationTest
{
    protected function setUp()
    {
        parent::setUp();

        if (!extension_loaded('mysql')) {
            $this->fail('The phpunit group integration-mysql was enabled, but the extension is not loaded.');
        }

        $this->adapter = new Adapter([
            'driver'         => 'Pdo',
            'dsn'            => sprintf('mysql:dbname=%s;host=%s', $GLOBALS['SLOTH_INTEGRATION_DBNAME'], $GLOBALS['SLOTH_INTEGRATION_HOSTNAME']),
            'username'       => $GLOBALS['SLOTH_INTEGRATION_USERNAME'],
            'password'       => $GLOBALS['SLOTH_INTEGRATION_PASSWORD'],
            'driver_options' => array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''
            ),
        ]);
        // mprd($this->adapter);
        // mprd($this->adapter->query('select * from "'.$this->testTableName.'"')->execute());
    }

    protected function setupTestTable()
    {
        $this->dropTestTable();
        // CREATE TABLE tk (col1 INT, col2 CHAR(5), col3 DATE)
        $statement = $this->adapter->query('
            CREATE TABLE '.$this->testTableName.' (
                id_user INT NOT NULL AUTO_INCREMENT,
                crt_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                short_name CHAR(16) NOT NULL,
                is_active boolean NOT NULL DEFAULT false,
                comment text,
                PRIMARY KEY (id_user),
                UNIQUE (short_name)
            )
        ');
        $result = $statement->execute();
        // foreach($result as $data){
        //    var_dump($data);
        // }
    }

    protected function dropTestTable()
    {
        if (false !== $this->adapter->query('show tables like "'.$this->testTableName.'"')->execute()->current()) {
            $this->adapter->query("drop table ".$this->testTableName)->execute();
        }
    }
}
