<?php

namespace BrsZfSloth\Sql\Where;

use BrsZfSloth\Sql\Where\In;

/**
 * Zasada klauzuli where "nie w".
 *
 * @author Tomasz Borys <t.borys@core.com.pl>
 * @version 1.0 2010-11-22 17:21:12
 */
class NotIn extends In {

    /**
     * @see     App_Model_Rule_Where_Where::$_condTpl
     * @var     string
     */
    protected $_condTpl = ':field NOT IN (?)';

}