<?php
namespace BrsZfSloth\Sql;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\StatementContainerInterface;
use Zend\Db\Sql\ExpressionInterface;
use Zend\Db\Sql\PreparableSqlInterface;
use Zend\Db\Sql\Predicate\PredicateInterface;

use BrsZfSloth\Exception;
use BrsZfSloth\Exception\ExceptionTools;
use BrsZfSloth\Definition\Definition;
use BrsZfSloth\Definition\DefinitionAwareInterface;

class Expr implements PredicateInterface
{
    const UNDEFINED = '__UN_DE_FI_NED__';
    const DEFAULT_DEFINITION_ALIAS = 'DEFAULT_ALIAS';

    protected $expr;
    protected $parseCache;
    protected $params = array();
    protected $fields = array();
    protected $definitions = array();

    public function __construct($expr)
    {
        $this->expr = (string) $expr;
    }

    public function __toString()
    {
        return $this->expr;
    }

    public function setDefaultDefinition($definition)
    {
        return $this->setDefinition($definition, self::DEFAULT_DEFINITION_ALIAS);
    }

    public function setDefinition($definition, $alias = null)
    {
        return $this->setDef($definition, $alias, true);
    }

    public function addDefinition($definition, $alias = null)
    {
        return $this->setDef($definition, $alias, false);
    }
    public function addDefinitions(array $definitions)
    {
        foreach ($definitions as $alias => $def) {
            $this->addDefinition($def, is_string($alias) ? $alias : null);
        }
        return $this;
    }
    protected function setDef($definition, $alias = null, $force = false)
    {
        if ($definition instanceof DefinitionAwareInterface) {
            $definition = $definition->getDefinition();
        } elseif (! $definition instanceof Definition) {
            throw new Exception\InvalidArgumentException(
                ExceptionTools::msg('definition must be instance of Definition or DefinitionAwareInterface, given %s', $definition)
            );
        }
        if (null === $alias) {
            $alias = $definition->getName();
        }

        if (! array_key_exists($alias, $this->definitions) || $force) {
            $this->definitions[$alias] = $definition;

        } elseif (spl_object_hash($this->definitions[$alias]) !== spl_object_hash($definition)) {
            throw new Exception\RuntimeException(
                ExceptionTools::msg('definition under alias %s already existst in expr %s', $alias, $this)
            );
        }
        return $this;
    }

    public function getDefinitions()
    {
        return $this->definitions;
    }

    protected function getDefinitionByAlias($alias)
    {
        if (! array_key_exists($alias, $this->definitions)) {
            throw new Exception\RuntimeException(
                ExceptionTools::msg('definition not exists for alias "%s" in expression %s', $alias, $this)
            );
        }
        return $this->definitions[$alias];
    }

    /**
     * @param string|array $name
     * @param mixed $value
     */
    public function setParam($name, $value = self::UNDEFINED)
    {
        if (is_array($name)) {
            foreach ($name as $k=>$v) {
                $this->setParam($k, $v);
            }
        } elseif (!in_array((string) $name, $this->getParsedParams())) {
            throw new Exception\NotExistsException(sprintf('Param "%s" not exists in expression "%s"', $name, $this));
        } elseif (self::UNDEFINED === $value) {
            throw new Exception\InvalidArgumentException(sprintf('Value for param "%s" could not be undefined in expression "%s"', $name, $this));
        } else {
            $this->params[(string) $name] = $value;
        }
        return $this;
    }

    public function getParam($name = null)
    {
        if (null === $name) {
            return $this->params;
        } elseif (array_key_exists($name, $this->params)) {
            return $this->params[$name];
        } else {
            throw new Exception\RuntimeException(sprintf('Param "%s" not set in expr %s', $name, $this));
        }
    }

    public function getParsedFields()
    {
        return array_filter($this->parse()['fields'], function($a){
            return is_string($a) && trim($a) !== "";
        });
    }

    public function getParsedParams()
    {
        return array_filter($this->parse()['params'], function($a){
            return is_string($a) && trim($a) !== "";
        });
    }

    protected function parse()
    {
        if (null !== $this->parseCache) {
            return $this->parseCache;
        }

        $expr = $this->expr;
        // $expr = '{Fid\Ba.field}={Fname} > :Psrame :Pdame {Fparame}=:Pparame';
        // $expr = '{Fid}';
        // $expr = 'aaa {Fid}';
        // $expr = ':Pid';
        // $expr = 'aaa :Pid';
        // $expr = 'aaa :Pid::text';

        $expr = str_replace('::', ';;', $expr); // because while sql cast ::text regexp catch as param

        $result = [
            'matched' => [],
            'fields' => [],
            'params' => []
        ];

        // if (preg_match_all('/\{([\w\.\\\\]+)\}|^|[^\:]\:(\w+)/', $this->expr, $m)) {
        // if (preg_match_all('/\{(?P<identifier>[\w\.\\\\]+)\}|\:(?P<param>\w+)/', $expr, $m)) {
        if (preg_match_all('/\{([\w\.\\\\]+)\}|\:(\w+)/', $expr, $m)) {
            // debuge($m);
            $result['matched'] = $m[0];
            $result['fields'] = $m[1];
            $result['params'] = $m[2];
        }
        return $result;
    }

    public function render(array $params = array())
    {
        $params = array_merge($this->getParam(), $params);

        // createing replacments
        $replace = array();
        foreach ($this->getParsedParams() as $param) {
            if (array_key_exists($param, $params)) {
                // $replace[sprintf(':%s', $param)] = $this->quote($params[$param]);
                $replace[sprintf(':%s', $param)] = $params[$param];
            } else {
                throw new Exception\RuntimeException(sprintf('Undefined param "%s" in expression "%s"', $param, $this->expr));
            }
        }
        foreach ($this->getParsedFields() as $field) {
            // if (isset($fields[$field])) {
                $replace[sprintf('{%s}', $field)] = $this->getIdentifier($field);
            // } else {
            //     throw new Exception\RuntimeException(sprintf('Undefined field "%s" in expression "%s"', $field, $this->expr));
            // }
        }
        return new self(str_replace(array_keys($replace), array_values($replace), $this->expr));
    }

    protected function getIdentifier($field)
    {
        if (strpos($field, '.')) {
            list($alias, $fieldName) = explode('.', $field);
        } else {
            $alias = self::DEFAULT_DEFINITION_ALIAS;
            $fieldName = $field;
        }
        $definition = $this->getDefinitionByAlias($alias);
        //ExceptionTools::msg('definition alias no match for identifier "%s" in expression %s', $field, $this)
        $identifier = $definition->getField($fieldName)->getMapping();

        if ($table = $definition->getTable()) {
            return sprintf('%s.%s', $table, $identifier);
        }
        return $identifier;
    }

    public function getExpressionData()
    {
        $parsed = $this->parse();

        $exprData = array(
            'e' => str_replace('%', '%%', $this->expr),  // expr will be used in vsprintf()
            'p' => array(), // params
            't' => array(), // types
        );

        foreach ($parsed['matched'] as $pos => $m) {
            $f = trim($parsed['fields'][$pos]);
            $p = trim($parsed['params'][$pos]);
            // debuge($parsed);

            // matched is field
            if ($f) {
                $exprData['e'] = str_replace($m, '%s', $exprData['e']);
                $exprData['p'][] = $this->getIdentifier($f);
                $exprData['t'][] = ExpressionInterface::TYPE_IDENTIFIER;

            // matched is param
            } else {
                $value = $this->getParam($p);
                // debuge(sprintf('test %d', $value));

                // XXX na razie dla intów też jako %s bo robi się np '2' i to w sprintf zamienia się w 0
                // może trzeba by zmieniać typ na LITERAL wtedy?
                $sprintfExpr = is_numeric($value) ? '%s' : '%s';

                $exprData['e'] = str_replace($m, $sprintfExpr, $exprData['e']);
                $exprData['p'][] = $value;
                $exprData['t'][] = ExpressionInterface::TYPE_VALUE;
            }
        }
        return array(
            // array(
            //     '%s = %2$f',
            //     array((string)$this->render(), 122),
            //     array(ExpressionInterface::TYPE_IDENTIFIER, ExpressionInterface::TYPE_VALUE)
            // )
            array_values($exprData),
        );
    }
}