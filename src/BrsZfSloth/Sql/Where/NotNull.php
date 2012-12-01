<?php

namespace BrsZfSloth\Sql\Where;

use BrsZfSloth\Sql\Where;

/**
 * Zasada klauzuli where "czy nie null".
 *
 * @author Tomasz Borys <t.borys@core.com.pl>
 * @version 1.0 2010-11-22 15:10:11
 */
class NotNull extends Where {

    /**
     * @see     App_Model_Rule_Where_Where::$_requiredValue
     * @var     bool
     */
    protected $_requiredValue = false;

    /**
     * @see     App_Model_Rule_Where_Where::$_condTpl
     * @var     string
     */
    protected $_condTpl = ':field IS NOT NULL';

}